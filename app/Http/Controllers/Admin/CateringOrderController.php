<?php

namespace App\Http\Controllers\Admin;

use App\Catering\CateringDesk;
use App\Catering\CateringException;
use App\Enums\CateringOrderStatus;
use App\Enums\CateringPaymentKind;
use App\Enums\EnquiryKind;
use App\Enums\LedgerAccount;
use App\Enums\LedgerHead;
use App\Enums\PaymentMode;
use App\Http\Controllers\Controller;
use App\Models\CateringOrder;
use App\Models\CateringPackage;
use App\Models\CateringPayment;
use App\Models\CateringRosterEntry;
use App\Models\Enquiry;
use App\Models\User;
use App\Support\FormOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Console → Catering: the orders board (enquiry → quoted → advance → served → settled), the
 * selected order with its quote, money, serving team and expenses, and CT receipts.
 * Catering in-charge and Admin (route middleware can:catering.manage).
 */
class CateringOrderController extends Controller
{
    /** Expense heads offered for an order (wages are paid from the serving team). */
    public const EXPENSE_HEADS = [LedgerHead::Groceries, LedgerHead::Wages, LedgerHead::OtherExpense];

    public function __construct(private readonly CateringDesk $desk) {}

    public function index(Request $request): View
    {
        $closed = $request->query('show') === 'closed';

        $orders = CateringOrder::query()
            ->whereIn('status', $closed ? [CateringOrderStatus::Lost->value, CateringOrderStatus::Cancelled->value] : array_map(fn (CateringOrderStatus $s): string => $s->value, CateringOrderStatus::board()))
            ->unless($closed, fn ($q) => $q->where(fn ($q) => $q->where('status', '!=', CateringOrderStatus::Settled->value)->orWhere('settled_at', '>=', now()->subDays(60))))
            ->with('package')
            ->orderBy('event_date')
            ->get();

        $selected = $request->filled('order')
            ? CateringOrder::query()->with(['package', 'roster', 'payments.receiver', 'expenses.recorder', 'enquiry'])->find($request->integer('order'))
            : null;
        $selected ??= $orders->first(fn (CateringOrder $o): bool => ! $o->status->isClosed()) ?? $orders->first();
        $selected?->loadMissing(['package', 'roster', 'payments.receiver', 'expenses.recorder', 'enquiry']);

        return view('admin.catering.index', [
            'closed' => $closed,
            'columns' => $closed ? [] : collect(CateringOrderStatus::board())->mapWithKeys(fn (CateringOrderStatus $s): array => [$s->value => $orders->where('status', $s)->values()]),
            'orders' => $orders,
            'order' => $selected,
            'summary' => $selected !== null ? $this->desk->summary($selected) : null,
            'packages' => CateringPackage::query()->orderBy('sort')->get(),
            'closedCount' => CateringOrder::query()->whereIn('status', [CateringOrderStatus::Lost->value, CateringOrderStatus::Cancelled->value])->count(),
            'openEnquiries' => Enquiry::query()->where('kind', EnquiryKind::Catering->value)->whereNull('handled_at')->latest()->get(),
            'expenseHeads' => self::EXPENSE_HEADS,
        ]);
    }

    public function create(Request $request): View
    {
        $enquiry = $request->filled('enquiry')
            ? Enquiry::query()->where('kind', EnquiryKind::Catering->value)->find($request->integer('enquiry'))
            : null;
        $payload = $enquiry->payload ?? [];
        $package = isset($payload['package']) ? CateringPackage::query()->where('slug', $payload['package'])->first() : null;

        return view('admin.catering.create', [
            'enquiry' => $enquiry,
            'prefill' => [
                'customer_name' => $enquiry?->name,
                'phone' => $enquiry?->phone,
                'occasion' => $payload['occasion'] ?? null,
                'event_date' => $payload['event_date'] ?? null,
                'guests' => $payload['guests'] ?? null,
                'package_id' => $package?->id,
                'is_member' => (bool) ($payload['is_member'] ?? false),
                'venue' => $payload['venue'] ?? null,
            ],
            'packages' => CateringPackage::query()->orderBy('sort')->get(),
            'occasions' => FormOptions::OCCASIONS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enquiry_id' => ['nullable', 'integer', Rule::exists('enquiries', 'id')->where('kind', EnquiryKind::Catering->value)],
            'customer_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9 ]{10,15}$/'],
            'occasion' => ['nullable', Rule::in(FormOptions::keys(FormOptions::OCCASIONS))],
            'event_date' => ['required', 'date', 'after_or_equal:today'],
            'guests' => ['required', 'integer', 'min:1', 'max:5000'],
            'package_id' => ['nullable', 'integer', Rule::exists('catering_packages', 'id')],
            'is_member' => ['sometimes', 'boolean'],
            'venue' => ['nullable', 'string', 'max:255'],
        ]);

        $enquiry = isset($data['enquiry_id']) ? Enquiry::query()->find($data['enquiry_id']) : null;
        unset($data['enquiry_id']);
        $data['is_member'] = $request->boolean('is_member');
        $data['package_id'] = isset($data['package_id']) ? (int) $data['package_id'] : null;
        $data['guests'] = (int) $data['guests'];

        /** @var array{customer_name: string, phone: string, occasion: ?string, event_date: string, guests: int, package_id: ?int, is_member: bool, venue: ?string} $data */
        $order = $this->desk->open($data + ['occasion' => null, 'venue' => null], $this->user($request), $enquiry);

        return redirect()->route('admin.catering.index', ['order' => $order->id])->with('status', 'Enquiry added to the board.');
    }

    public function quote(Request $request, CateringOrder $order): RedirectResponse
    {
        $data = $request->validate([
            'guests' => ['required', 'integer', 'min:1', 'max:5000'],
            'package_id' => ['required', 'integer', Rule::exists('catering_packages', 'id')],
            'valid_until' => ['nullable', 'date', 'after_or_equal:today'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        return $this->attempt($order, fn () => $this->desk->quote(
            $order,
            (int) $data['guests'],
            CateringPackage::query()->findOrFail($data['package_id']),
            isset($data['valid_until']) ? Carbon::parse($data['valid_until']) : null,
            $data['note'] ?? null,
            $this->user($request),
        ), 'Quote saved. Send it to the customer on WhatsApp.');
    }

    public function receive(Request $request, CateringOrder $order): RedirectResponse
    {
        $data = $request->validate([
            'kind' => ['required', Rule::in([CateringPaymentKind::Advance->value, CateringPaymentKind::Part->value, CateringPaymentKind::Final->value])],
            'amount' => ['required', 'integer', 'min:1', 'max:10000000'],
            'mode' => ['required', Rule::in([PaymentMode::Cash->value, PaymentMode::Upi->value])],
            'upi_ref' => ['nullable', 'required_if:mode,upi', 'string', 'regex:/^[A-Za-z0-9]{6,40}$/'],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        try {
            $payment = $this->desk->receive($order, CateringPaymentKind::from($data['kind']), (int) $data['amount'], PaymentMode::from($data['mode']), $data['upi_ref'] ?? null, $data['note'] ?? null, $this->user($request));
        } catch (CateringException $e) {
            return back()->withInput()->with('error', $e->getMessage())->with('status_replace', $e->replace);
        }

        return redirect()->route('admin.catering.receipt', $payment)
            ->with('status', 'Payment recorded in the Catering book. Receipt :receipt.')
            ->with('status_replace', ['receipt' => (string) $payment->receipt_no]);
    }

    public function receipt(CateringPayment $payment): View
    {
        abort_if($payment->isRefund(), 404);
        $payment->load(['order.package', 'receiver']);
        $order = $payment->order;

        $text = __('Gede Maitree Club. Receipt :receipt: :amount received from :name for serving on :date (:kind). Thank you.', [
            'receipt' => (string) $payment->receipt_no,
            'amount' => rupees($payment->amount),
            'name' => $order->customer_name,
            'date' => local_date($order->event_date),
            'kind' => $payment->kind->label(),
        ]);

        return view('admin.catering.receipt', [
            'payment' => $payment,
            'order' => $order,
            'summary' => $this->desk->summary($order),
            'whatsapp' => self::whatsapp($order->phone, $text),
        ]);
    }

    public function served(Request $request, CateringOrder $order): RedirectResponse
    {
        $data = $request->validate(['guests' => ['required', 'integer', 'min:1', 'max:5000']]);

        return $this->attempt($order, fn () => $this->desk->served($order, (int) $data['guests'], $this->user($request)), 'Marked as served. The final bill is ready.');
    }

    public function lost(Request $request, CateringOrder $order): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', Rule::in(array_keys(self::lostReasons()))]]);

        return $this->attempt($order, fn () => $this->desk->lost($order, self::lostReasons()[$data['reason']]['label'], $this->user($request)), 'Order marked as lost.');
    }

    public function cancel(Request $request, CateringOrder $order): RedirectResponse
    {
        $data = $request->validate([
            'refund' => ['required', 'integer', 'min:0', 'max:10000000'],
            'mode' => ['required', Rule::in([PaymentMode::Cash->value, PaymentMode::Upi->value])],
            'reason' => ['required', 'string', 'min:3', 'max:200'],
        ]);

        return $this->attempt($order, fn () => $this->desk->cancel($order, (int) $data['refund'], PaymentMode::from($data['mode']), $data['reason'], $this->user($request)), 'Booking cancelled. Any refund is entered in the Catering book.');
    }

    public function expense(Request $request, CateringOrder $order): RedirectResponse
    {
        $data = $request->validate([
            'head' => ['required', Rule::in(array_map(fn (LedgerHead $h): string => $h->value, self::EXPENSE_HEADS))],
            'amount' => ['required', 'integer', 'min:1', 'max:10000000'],
            'account' => ['required', Rule::enum(LedgerAccount::class)],
            'note' => ['nullable', 'string', 'max:120'],
            'bill' => ['nullable', 'image', 'max:8192'],
        ]);

        return $this->attempt($order, fn () => $this->desk->expense($order, LedgerHead::from($data['head']), (int) $data['amount'], LedgerAccount::from($data['account']), $data['note'] ?? null, $this->user($request), $request->file('bill')), 'Expense saved against this order.');
    }

    public function wages(Request $request, CateringOrder $order): RedirectResponse
    {
        $data = $request->validate(['account' => ['required', Rule::enum(LedgerAccount::class)]]);

        return $this->attempt($order, fn () => $this->desk->payWages($order, LedgerAccount::from($data['account']), $this->user($request)), 'Wages entered in the Catering book.');
    }

    public function addServer(Request $request, CateringOrder $order): RedirectResponse
    {
        $data = $request->validate([
            'server_name' => ['required', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'regex:/^\+?[0-9 ]{10,15}$/'],
            'is_leader' => ['sometimes', 'boolean'],
            'wage' => ['required', 'integer', 'min:0', 'max:100000'],
        ]);

        return $this->attempt($order, fn () => $this->desk->addServer($order, [
            'server_name' => $data['server_name'],
            'phone' => $data['phone'] ?? null,
            'is_leader' => $request->boolean('is_leader'),
            'wage' => (int) $data['wage'],
        ], $this->user($request)), 'Added to the serving team.');
    }

    public function confirmServer(CateringOrder $order, CateringRosterEntry $server): RedirectResponse
    {
        abort_unless($server->order_id === $order->id, 404);
        $server->forceFill(['confirmed' => ! $server->confirmed])->save();

        return redirect()->route('admin.catering.index', ['order' => $order->id]);
    }

    public function removeServer(Request $request, CateringOrder $order, CateringRosterEntry $server): RedirectResponse
    {
        abort_unless($server->order_id === $order->id, 404);

        return $this->attempt($order, fn () => $this->desk->removeServer($server, $this->user($request)), 'Removed from the serving team.');
    }

    /**
     * Why an enquiry or quote was lost (key => English label, translated where shown; English is stored).
     *
     * @return array<string, array{label: string}>
     */
    public static function lostReasons(): array
    {
        return [
            'price' => ['label' => 'Price too high'],
            'date' => ['label' => 'Date not free'],
            'other' => ['label' => 'Chose another caterer'],
            'off' => ['label' => 'Event called off'],
            'silent' => ['label' => 'No reply'],
        ];
    }

    /**
     * WhatsApp link with the text ready, or null without a usable number.
     */
    public static function whatsapp(?string $phone, string $text): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';
        if (strlen($digits) === 10) {
            $digits = '91'.$digits;
        }

        return strlen($digits) >= 12 ? 'https://wa.me/'.$digits.'?text='.rawurlencode($text) : null;
    }

    /**
     * @param  callable(): mixed  $action
     */
    private function attempt(CateringOrder $order, callable $action, string $done): RedirectResponse
    {
        try {
            $action();
        } catch (CateringException $e) {
            return redirect()->route('admin.catering.index', ['order' => $order->id])->withInput()
                ->with('error', $e->getMessage())->with('status_replace', $e->replace);
        }

        return redirect()->route('admin.catering.index', ['order' => $order->id])->with('status', $done);
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        assert($user instanceof User);

        return $user;
    }
}
