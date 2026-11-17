<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookCode;
use App\Enums\PaymentMode;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Payments\ReceiptRegister;
use App\Support\Csv;
use App\Support\DueReminder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Console → Receipts register: every receipt number in order, cancelled ones kept with
 * their reason, a check that no number is missing, a printable receipt and a CSV export.
 */
class ReceiptController extends Controller
{
    public function index(Request $request, ReceiptRegister $register): View
    {
        Gate::authorize('viewAny', Payment::class);

        [$mode, $search, $year] = $this->filters($request);
        $receipts = $this->query($mode, $search, $year)->paginate(50)->withQueryString();

        $monthStart = now()->startOfMonth();
        $thisMonth = Payment::query()->where('status', PaymentStatus::Confirmed->value)->where('paid_at', '>=', $monthStart)
            ->selectRaw("COUNT(*) AS receipts, COALESCE(SUM(amount), 0) AS total, COALESCE(SUM(CASE WHEN mode = 'cash' THEN amount ELSE 0 END), 0) AS cash")
            ->toBase()->first();

        return view('admin.receipts.index', [
            'receipts' => $receipts,
            'mode' => $mode,
            'search' => $search,
            'year' => $year,
            'years' => range((int) now()->format('Y'), 2014),
            'series' => $register->check(BookCode::Club, $year),
            'cancelled' => Payment::query()->where('status', PaymentStatus::Cancelled->value)->whereYear('paid_at', $year)->count(),
            'monthReceipts' => (int) ($thisMonth->receipts ?? 0),
            'monthTotal' => (int) ($thisMonth->total ?? 0),
            'monthCash' => (int) ($thisMonth->cash ?? 0),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', Payment::class);

        [$mode, $search, $year] = $this->filters($request);

        $rows = $this->query($mode, $search, $year)->lazy(200)->map(fn (Payment $p): array => [
            $p->receipt_no,
            $p->paid_at?->format('Y-m-d'),
            $p->family->family_no,
            $p->family->head_name,
            $p->monthsLabel(),
            $p->mode->englishLabel(),
            $p->upi_ref ?? $p->gateway_payment_id,
            $p->status->englishLabel(),
            $p->status === PaymentStatus::Cancelled ? $p->note : null,
            $p->receiver?->name,
            $p->amount,
        ]);

        return Csv::download("receipts-{$year}.csv", ['Receipt', 'Date', 'Family', 'Received from', 'Months', 'Mode', 'UTR / payment id', 'Status', 'Cancel reason', 'Received by', 'Amount (₹)'], $rows);
    }

    public function show(Payment $payment): View
    {
        Gate::authorize('view', $payment);
        abort_if($payment->receipt_no === null, 404);

        $payment->load(['dues', 'family', 'receiver', 'canceller']);

        return view('admin.receipts.show', [
            'payment' => $payment,
            'whatsapp' => DueReminder::link($payment->family, DueReminder::receiptMessage((string) $payment->receipt_no, $payment->amount)),
        ]);
    }

    /**
     * @return array{0: ?string, 1: string, 2: int}
     */
    private function filters(Request $request): array
    {
        $request->validate([
            'mode' => ['nullable', Rule::in([...PaymentMode::values(), 'cancelled'])],
            'q' => ['nullable', 'string', 'max:60'],
            'year' => ['nullable', 'integer', 'min:2014', 'max:2100'],
        ]);

        return [
            $request->filled('mode') ? $request->string('mode')->toString() : null,
            trim($request->string('q')->toString()),
            $request->integer('year', (int) now()->format('Y')),
        ];
    }

    /**
     * @return Builder<Payment>
     */
    private function query(?string $mode, string $search, int $year): Builder
    {
        return Payment::query()
            ->receipted()
            ->with(['family', 'dues', 'receiver'])
            ->whereYear('paid_at', $year)
            ->when($mode === 'cancelled', fn ($q) => $q->where('status', PaymentStatus::Cancelled->value))
            ->when($mode !== null && $mode !== 'cancelled', fn ($q) => $q->where('mode', $mode))
            ->when($search !== '', function ($q) use ($search): void {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%';
                $q->where(fn ($w) => $w->where('receipt_no', 'ilike', $like)
                    ->orWhereHas('family', fn ($f) => $f->where('family_no', 'ilike', $like)->orWhere('head_name', 'ilike', $like)->orWhere('head_name_bn', 'ilike', $like)));
            })
            ->orderByDesc('receipt_no');
    }
}
