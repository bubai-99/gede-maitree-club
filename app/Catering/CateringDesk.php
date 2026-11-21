<?php

namespace App\Catering;

use App\Enums\BookCode;
use App\Enums\CateringOrderStatus;
use App\Enums\CateringPaymentKind;
use App\Enums\LedgerAccount;
use App\Enums\LedgerHead;
use App\Enums\LedgerType;
use App\Enums\PaymentMode;
use App\Models\Book;
use App\Models\CateringOrder;
use App\Models\CateringPackage;
use App\Models\CateringPayment;
use App\Models\CateringRosterEntry;
use App\Models\Enquiry;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Payments\Khata;
use App\Support\ReceiptNumbers;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * The serving team's bookings, from enquiry to settled:
 *
 *   enquiry → quoted (guests × rate, 20% advance asked) → advance (booking confirmed)
 *   → done (served; final guest count) → settled (balance paid)
 *
 * An enquiry or quote can be lost; a quoted or confirmed booking can be cancelled with a refund.
 * Money received gets a CT receipt and is income in the Catering book; refunds, wages and other
 * expenses are expenses in the Catering book with the order as their source, so the order's
 * profit is income minus expenses.
 */
class CateringDesk
{
    public function __construct(private readonly ReceiptNumbers $numbers, private readonly Khata $khata) {}

    /**
     * A new enquiry on the board, typed in or made from a website catering enquiry.
     *
     * @param  array{customer_name: string, phone: string, occasion: ?string, event_date: string, guests: int, package_id: ?int, is_member: bool, venue: ?string}  $data
     */
    public function open(array $data, User $by, ?Enquiry $from = null): CateringOrder
    {
        return DB::transaction(function () use ($data, $by, $from): CateringOrder {
            $package = isset($data['package_id']) ? CateringPackage::query()->find($data['package_id']) : null;

            $order = CateringOrder::query()->create([
                ...$data,
                'enquiry_id' => $from?->id,
                'rate' => $package !== null ? $this->rateFor($package, $data['is_member']) : null,
                'status' => CateringOrderStatus::Enquiry,
                'created_by' => $by->id,
            ]);
            $order->forceFill(['quoted_amount' => $order->total() ?: null])->save();

            if ($from !== null && $from->handled_at === null) {
                $from->forceFill(['handled_at' => now(), 'handled_by' => $by->id])->save();
            }

            $this->log($order, $by, 'created', "Catering enquiry {$order->number()}: {$order->customer_name}, {$order->guests} guests");

            return $order;
        });
    }

    /**
     * Fix guests and package (and so the rate) and send the quote. An enquiry becomes quoted;
     * a quoted or confirmed booking keeps its stage with the new numbers.
     *
     * @throws CateringException
     */
    public function quote(CateringOrder $order, int $guests, CateringPackage $package, ?CarbonInterface $validUntil, ?string $note, User $by): CateringOrder
    {
        return DB::transaction(function () use ($order, $guests, $package, $validUntil, $note, $by): CateringOrder {
            $locked = $this->lock($order);

            if (! in_array($locked->status, [CateringOrderStatus::Enquiry, CateringOrderStatus::Quoted, CateringOrderStatus::Advance], true)) {
                throw new CateringException('Guests and package can only be changed before the order is served.');
            }

            $rate = $this->rateFor($package, $locked->is_member);
            $locked->forceFill([
                'guests' => $guests,
                'package_id' => $package->id,
                'rate' => $rate,
                'quoted_amount' => $guests * $rate,
                'quote_valid_until' => $validUntil?->toDateString(),
                'quote_note' => $note,
                'status' => $locked->status === CateringOrderStatus::Enquiry ? CateringOrderStatus::Quoted : $locked->status,
                'quoted_at' => $locked->quoted_at ?? now(),
            ])->save();

            $this->log($locked, $by, 'quoted', "Catering quote {$locked->number()}: {$guests} × ₹{$rate} = ₹{$locked->total()}");

            return $this->fresh($order, $locked);
        });
    }

    /**
     * Take money: an advance confirms a quoted booking; a final payment that clears the balance
     * of a served order settles it. Each gets the next CT receipt and a line in the Catering book.
     *
     * @throws CateringException
     */
    public function receive(CateringOrder $order, CateringPaymentKind $kind, int $amount, PaymentMode $mode, ?string $upiRef, ?string $note, User $by): CateringPayment
    {
        return DB::transaction(function () use ($order, $kind, $amount, $mode, $upiRef, $note, $by): CateringPayment {
            $locked = $this->lock($order);
            $balance = $this->balance($locked);

            if ($kind === CateringPaymentKind::Refund) {
                throw new CateringException('Refunds are given by cancelling the booking.');
            }
            if ($locked->status === CateringOrderStatus::Enquiry || $locked->status->isClosed()) {
                throw new CateringException('Money can be taken once the quote is sent and until the order is settled.');
            }
            if ($locked->rate === null) {
                throw new CateringException('Choose a package and send the quote first.');
            }
            if ($kind === CateringPaymentKind::Final && $locked->status !== CateringOrderStatus::Done) {
                throw new CateringException('The final bill comes after the order is marked as served.');
            }
            if ($amount > $balance) {
                throw new CateringException('That is more than the balance of :balance.', ['balance' => rupees($balance)]);
            }

            $payment = CateringPayment::query()->create([
                'order_id' => $locked->id,
                'amount' => $amount,
                'kind' => $kind,
                'mode' => $mode,
                'upi_ref' => $mode === PaymentMode::Upi ? $upiRef : null,
                'note' => $note,
                'paid_on' => now()->toDateString(),
                'received_by' => $by->id,
                'receipt_no' => $this->numbers->next(BookCode::Catering),
            ]);

            $line = new LedgerEntry([
                'book_id' => Book::forCode(BookCode::Catering)->id,
                'entry_date' => now()->toDateString(),
                'type' => LedgerType::Income,
                'account' => $mode->account(),
                'head' => LedgerHead::CateringIncome,
                'amount' => $amount,
                'note' => mb_substr("{$payment->receipt_no} · {$locked->number()} · {$locked->customer_name}", 0, 255),
                'recorded_by' => $by->id,
            ]);
            $line->source()->associate($payment);
            $line->save();
            $payment->forceFill(['ledger_entry_id' => $line->id])->save();

            if ($locked->status === CateringOrderStatus::Quoted && $kind === CateringPaymentKind::Advance) {
                $locked->forceFill(['status' => CateringOrderStatus::Advance])->save();
            }
            if ($locked->status === CateringOrderStatus::Done && $amount === $balance) {
                $locked->forceFill(['status' => CateringOrderStatus::Settled, 'settled_at' => now()])->save();
            }

            activity('catering')->performedOn($payment)->causedBy($by)
                ->withProperties(['order' => $locked->number(), 'receipt' => $payment->receipt_no, 'kind' => $kind->value, 'amount' => $amount, 'mode' => $mode->value])
                ->event('created')
                ->log("Catering receipt {$payment->receipt_no}: ₹{$amount} ({$kind->englishLabel()}) for {$locked->number()}");

            $order->setRawAttributes($locked->getAttributes(), true);

            return $payment;
        });
    }

    /**
     * Served: the final guest count sets the bill. Extra guests are charged at the same rate.
     *
     * @throws CateringException
     */
    public function served(CateringOrder $order, int $guests, User $by): CateringOrder
    {
        return DB::transaction(function () use ($order, $guests, $by): CateringOrder {
            $locked = $this->lock($order);

            if ($locked->status !== CateringOrderStatus::Advance) {
                throw new CateringException('Only a confirmed booking can be marked as served.');
            }

            $locked->forceFill(['guests' => $guests, 'quoted_amount' => $guests * (int) $locked->rate, 'status' => CateringOrderStatus::Done, 'served_at' => now()])->save();

            // Paid in full already (or more guests were paid for than came): nothing left to collect.
            if ($this->balance($locked) <= 0) {
                $locked->forceFill(['status' => CateringOrderStatus::Settled, 'settled_at' => now()])->save();
            }

            $this->log($locked, $by, 'served', "Catering order {$locked->number()} served: {$guests} guests, bill ₹{$locked->total()}");

            return $this->fresh($order, $locked);
        });
    }

    /**
     * The customer went elsewhere. Only before any money is taken.
     *
     * @throws CateringException
     */
    public function lost(CateringOrder $order, string $reason, User $by): CateringOrder
    {
        return DB::transaction(function () use ($order, $reason, $by): CateringOrder {
            $locked = $this->lock($order);

            if (! in_array($locked->status, [CateringOrderStatus::Enquiry, CateringOrderStatus::Quoted], true) || $this->received($locked) > 0) {
                throw new CateringException('Only an enquiry or a quote with no money taken can be marked as lost.');
            }

            $this->close($locked, CateringOrderStatus::Lost, $reason, $by);
            $this->log($locked, $by, 'lost', "Catering order {$locked->number()} lost: {$reason}");

            return $this->fresh($order, $locked);
        });
    }

    /**
     * Cancel a booking before it is served. Any refund (up to what was paid) is an expense in the
     * Catering book; what is kept stays as income.
     *
     * @throws CateringException
     */
    public function cancel(CateringOrder $order, int $refund, PaymentMode $mode, string $reason, User $by): CateringOrder
    {
        return DB::transaction(function () use ($order, $refund, $mode, $reason, $by): CateringOrder {
            $locked = $this->lock($order);
            $received = $this->received($locked);

            if (! in_array($locked->status, [CateringOrderStatus::Quoted, CateringOrderStatus::Advance], true)) {
                throw new CateringException('Only a quoted or confirmed booking can be cancelled.');
            }
            if ($refund > $received) {
                throw new CateringException('The refund cannot be more than the :paid received.', ['paid' => rupees($received)]);
            }

            if ($refund > 0) {
                $payment = CateringPayment::query()->create([
                    'order_id' => $locked->id,
                    'amount' => $refund,
                    'kind' => CateringPaymentKind::Refund,
                    'mode' => $mode,
                    'note' => mb_substr($reason, 0, 200),
                    'paid_on' => now()->toDateString(),
                    'received_by' => $by->id,
                ]);
                $line = new LedgerEntry([
                    'book_id' => Book::forCode(BookCode::Catering)->id,
                    'entry_date' => now()->toDateString(),
                    'type' => LedgerType::Expense,
                    'account' => $mode->account(),
                    'head' => LedgerHead::Refunds,
                    'amount' => $refund,
                    'note' => mb_substr("Refund · {$locked->number()} · {$locked->customer_name}", 0, 255),
                    'recorded_by' => $by->id,
                ]);
                $line->source()->associate($payment);
                $line->save();
                $payment->forceFill(['ledger_entry_id' => $line->id])->save();
            }

            $this->close($locked, CateringOrderStatus::Cancelled, $reason, $by);
            $this->log($locked, $by, 'cancelled', "Catering booking {$locked->number()} cancelled, refund ₹{$refund}: {$reason}");

            return $this->fresh($order, $locked);
        });
    }

    /**
     * An expense for this order in the Catering book (plates, transport, washing...), with a bill photo.
     *
     * @throws CateringException
     */
    public function expense(CateringOrder $order, LedgerHead $head, int $amount, LedgerAccount $account, ?string $note, User $by, ?UploadedFile $bill = null): LedgerEntry
    {
        if ($head->type() !== LedgerType::Expense) {
            throw new CateringException('Pick an expense head.');
        }
        if (in_array($order->status, [CateringOrderStatus::Lost], true)) {
            throw new CateringException('This order was lost; nothing can be spent on it.');
        }

        return $this->khata->record(Book::forCode(BookCode::Catering), [
            'type' => LedgerType::Expense,
            'head' => $head,
            'account' => $account,
            'amount' => $amount,
            'entry_date' => now()->toDateString(),
            'note' => mb_substr(trim("{$order->number()} · {$order->customer_name}".($note ? " · {$note}" : '')), 0, 255),
        ], $by, $bill, $order);
    }

    /**
     * Pay the serving team once the order is served: one Wages line for everyone on the roster.
     *
     * @throws CateringException
     */
    public function payWages(CateringOrder $order, LedgerAccount $account, User $by): LedgerEntry
    {
        return DB::transaction(function () use ($order, $account, $by): LedgerEntry {
            $locked = $this->lock($order);
            $total = (int) $locked->roster()->sum('wage');

            if (! in_array($locked->status, [CateringOrderStatus::Done, CateringOrderStatus::Settled], true)) {
                throw new CateringException('Wages are paid after the order is served.');
            }
            if ($locked->wages_paid_at !== null) {
                throw new CateringException('Wages for this order are already paid.');
            }
            if ($total <= 0) {
                throw new CateringException('Add the serving team and their wages first.');
            }

            $line = $this->expense($locked, LedgerHead::Wages, $total, $account, 'Serving team wages · '.$locked->roster()->count(), $by);
            $locked->forceFill(['wages_paid_at' => now()])->save();
            $order->setRawAttributes($locked->getAttributes(), true);

            return $line;
        });
    }

    /**
     * @param  array{server_name: string, phone: ?string, is_leader: bool, wage: int}  $data
     *
     * @throws CateringException
     */
    public function addServer(CateringOrder $order, array $data, User $by): CateringRosterEntry
    {
        if ($order->status->isClosed() && $order->status !== CateringOrderStatus::Settled) {
            throw new CateringException('This order is closed.');
        }
        if ($order->wages_paid_at !== null) {
            throw new CateringException('Wages are paid; the team can no longer change.');
        }

        return DB::transaction(function () use ($order, $data, $by): CateringRosterEntry {
            if ($data['is_leader']) {
                $order->roster()->update(['is_leader' => false]);
            }

            $entry = $order->roster()->create([...$data, 'confirmed' => false]);
            $this->log($order, $by, 'roster', "Serving team for {$order->number()}: added {$entry->server_name}");

            return $entry;
        });
    }

    /**
     * @throws CateringException
     */
    public function removeServer(CateringRosterEntry $entry, User $by): void
    {
        $order = $entry->order;

        if ($order->wages_paid_at !== null) {
            throw new CateringException('Wages are paid; the team can no longer change.');
        }

        $entry->delete();
        $this->log($order, $by, 'roster', "Serving team for {$order->number()}: removed {$entry->server_name}");
    }

    /**
     * Money in and out for one order.
     *
     * @return array{total: int, advance_due: int, received: int, refunded: int, balance: int, expenses: int, profit: int}
     */
    public function summary(CateringOrder $order): array
    {
        $payments = $order->payments()->get();
        $received = (int) $payments->reject(fn (CateringPayment $p): bool => $p->isRefund())->sum('amount');
        $refunded = (int) $payments->filter(fn (CateringPayment $p): bool => $p->isRefund())->sum('amount');
        $expenses = (int) $order->expenses()->where('type', LedgerType::Expense->value)->sum(DB::raw(LedgerEntry::SIGNED_AMOUNT));

        return [
            'total' => $order->total(),
            'advance_due' => $order->advanceDue(),
            'received' => $received,
            'refunded' => $refunded,
            'balance' => max(0, $order->total() - $received),
            'expenses' => $expenses,
            'profit' => $received - $refunded - $expenses,
        ];
    }

    /**
     * Per-guest rate: the package rate, or the member rate for a club family.
     */
    public function rateFor(CateringPackage $package, bool $member): int
    {
        return $member ? $package->memberRate() : $package->rate;
    }

    private function received(CateringOrder $order): int
    {
        return (int) $order->payments()->where('kind', '!=', CateringPaymentKind::Refund->value)->sum('amount');
    }

    private function balance(CateringOrder $order): int
    {
        return $order->total() - $this->received($order);
    }

    private function close(CateringOrder $order, CateringOrderStatus $status, string $reason, User $by): void
    {
        $order->forceFill(['status' => $status, 'closed_at' => now(), 'closed_reason' => mb_substr($reason, 0, 200), 'closed_by' => $by->id])->save();
    }

    private function lock(CateringOrder $order): CateringOrder
    {
        return CateringOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
    }

    private function fresh(CateringOrder $order, CateringOrder $locked): CateringOrder
    {
        $order->setRawAttributes($locked->getAttributes(), true);

        return $order;
    }

    private function log(CateringOrder $order, User $by, string $event, string $message): void
    {
        activity('catering')->performedOn($order)->causedBy($by)->event($event)->log($message);
    }
}
