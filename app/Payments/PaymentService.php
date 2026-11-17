<?php

namespace App\Payments;

use App\Enums\BookCode;
use App\Enums\DueStatus;
use App\Enums\Gateway;
use App\Enums\LedgerHead;
use App\Enums\LedgerType;
use App\Enums\PaymentMode;
use App\Enums\PaymentStatus;
use App\Models\Book;
use App\Models\Due;
use App\Models\Family;
use App\Models\Payment;
use App\Models\User;
use App\Support\ReceiptNumbers;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Every change to a family payment goes through here, inside a database transaction with
 * the payment row locked (SELECT ... FOR UPDATE). Confirming is idempotent: the checkout
 * callback, a webhook and the treasurer can all confirm the same payment and only the first
 * one issues a receipt number and writes the khata.
 */
class PaymentService
{
    /** Shown on a payment whose money arrived after its months were settled another way. */
    public const NOTE_OVERLAP = 'Some months were already paid or waived when this money arrived. Refund or adjust it.';

    /** Shown when Razorpay reports an amount that is not the payment's amount. */
    public const NOTE_AMOUNT = 'Razorpay reported a different amount. Check it in the Razorpay Dashboard.';

    public function __construct(private readonly ReceiptNumbers $numbers, private readonly DueService $dues) {}

    /**
     * A member starts paying for some of their family's unpaid months.
     *
     * @param  list<int>  $dueIds
     *
     * @throws PaymentException
     */
    public function start(Family $family, array $dueIds, PaymentGateway $gateway): Payment
    {
        $payment = DB::transaction(function () use ($family, $dueIds, $gateway): Payment {
            Family::query()->whereKey($family->id)->lockForUpdate()->first();

            $dueIds = array_values(array_unique($dueIds));
            $dues = Due::query()->where('family_id', $family->id)->whereIn('id', $dueIds)->payable()->orderBy('period')->get();

            if ($dues->isEmpty() || $dues->count() !== count($dueIds)) {
                throw new PaymentException('Some of the months you chose are already paid or waiting for the treasurer. Please choose again.');
            }

            // An earlier checkout that was never finished is replaced by this one.
            Payment::query()->where('family_id', $family->id)->where('status', PaymentStatus::Pending->value)
                ->update(['status' => PaymentStatus::Expired->value, 'updated_at' => now()]);

            $payment = Payment::query()->create([
                'family_id' => $family->id,
                'amount' => (int) $dues->sum('amount'),
                'mode' => $gateway->code() === Gateway::Razorpay ? PaymentMode::Online : PaymentMode::Upi,
                'gateway' => $gateway->code(),
                'status' => PaymentStatus::Pending,
            ]);
            $payment->forceFill(['reference' => self::reference($payment)])->save();
            $payment->dues()->attach($dues->modelKeys());

            return $payment;
        });

        try {
            $gateway->begin($payment);
        } catch (PaymentException $e) {
            $payment->forceFill(['status' => PaymentStatus::Failed, 'note' => $e->getMessage()])->save();

            throw $e;
        }

        return $payment;
    }

    /**
     * The member paid by UPI to the club and sends the 12-digit UTR for the treasurer to check.
     *
     * @throws PaymentException
     */
    public function submitUtr(Payment $payment, string $utr, User $member): Payment
    {
        try {
            return DB::transaction(function () use ($payment, $utr, $member): Payment {
                $locked = $this->lock($payment);

                if ($locked->gateway !== Gateway::ManualUpi || ! in_array($locked->status, [PaymentStatus::Pending, PaymentStatus::Expired], true)) {
                    throw new PaymentException('This payment has already been sent for confirmation.');
                }

                $stillPayable = Due::query()->whereIn('id', $this->dueIds($locked))->payable()->count();

                if ($stillPayable !== count($this->dueIds($locked))) {
                    throw new PaymentException('Some of the months you chose are already paid or waiting for the treasurer. Please choose again.');
                }

                $locked->forceFill([
                    'upi_ref' => $utr,
                    'status' => PaymentStatus::AwaitingConfirmation,
                    'submitted_at' => now(),
                ])->save();

                activity('payments')->performedOn($locked)->causedBy($member)
                    ->withProperties(['amount' => $locked->amount, 'utr' => $utr])
                    ->event('submitted')
                    ->log("UPI payment sent for confirmation by {$locked->family->family_no}");

                return $locked;
            });
        } catch (UniqueConstraintViolationException) {
            throw new PaymentException('This UTR is already on another payment. Check the number in your UPI app.');
        }
    }

    /**
     * Mark a payment as paid: receipt number from the Club book series, months marked paid,
     * an income line in the Club book (and the gateway fee as an expense), and the audit log.
     * Calling it again for a paid payment changes nothing.
     *
     * @param  array{gateway_payment_id?: string, gateway_method?: ?string, gateway_fee_paise?: ?int, gateway_tax_paise?: ?int}  $details
     *
     * @throws PaymentException
     */
    public function confirm(Payment $payment, ?User $by = null, array $details = []): Payment
    {
        return DB::transaction(function () use ($payment, $by, $details): Payment {
            $locked = $this->lock($payment);

            if ($locked->status === PaymentStatus::Confirmed) {
                return $locked;
            }

            if (! in_array($locked->status, PaymentStatus::open(), true)) {
                throw new PaymentException('This payment can no longer be confirmed.');
            }

            $dues = Due::query()->whereIn('id', $this->dueIds($locked))->orderBy('id')->lockForUpdate()->get();
            $open = $dues->filter(fn (Due $due): bool => $due->status === DueStatus::Due);
            $now = Carbon::now();

            $locked->forceFill([
                ...$details,
                'status' => PaymentStatus::Confirmed,
                'receipt_no' => $this->numbers->next(BookCode::Club, $now),
                'paid_at' => $now,
                'received_by' => $by !== null ? $by->id : $locked->received_by,
                'note' => $open->count() === $dues->count() ? $locked->note : self::NOTE_OVERLAP,
            ])->save();

            if ($open->isNotEmpty()) {
                Due::query()->whereIn('id', $open->modelKeys())->update(['status' => DueStatus::Paid->value, 'updated_at' => $now]);
            }

            $this->writeKhata($locked, $by);

            activity('payments')->performedOn($locked)->causedBy($by)
                ->withProperties(['receipt' => $locked->receipt_no, 'amount' => $locked->amount, 'mode' => $locked->mode->value])
                ->event('confirmed')
                ->log("Payment recorded: ₹{$locked->amount} from {$locked->family->family_no}, receipt {$locked->receipt_no}");

            return $locked;
        });
    }

    /**
     * Apply what Razorpay says about a payment (from the Payments API or a webhook).
     * Returns "paid", "failed", "waiting" or "ignored".
     *
     * @param  array<string, mixed>  $entity  Razorpay payment entity
     */
    public function applyRazorpayPayment(Payment $payment, array $entity): string
    {
        if (($entity['order_id'] ?? null) !== $payment->gateway_order_id || $payment->gateway_order_id === null) {
            return 'ignored';
        }

        $status = $entity['status'] ?? null;

        if ($status === 'captured') {
            if (($entity['amount'] ?? null) !== RazorpayGateway::toPaise($payment->amount) || ($entity['currency'] ?? null) !== 'INR') {
                $payment->forceFill(['note' => self::NOTE_AMOUNT])->save();
                activity('payments')->performedOn($payment)->event('review')->log("Razorpay amount does not match payment {$payment->reference}");

                return 'ignored';
            }

            $this->confirm($payment, null, [
                'gateway_payment_id' => is_string($entity['id'] ?? null) ? $entity['id'] : null,
                'gateway_method' => is_string($entity['method'] ?? null) ? $entity['method'] : null,
                'gateway_fee_paise' => is_int($entity['fee'] ?? null) ? $entity['fee'] : null,
                'gateway_tax_paise' => is_int($entity['tax'] ?? null) ? $entity['tax'] : null,
            ]);

            return 'paid';
        }

        if ($status === 'failed') {
            $reason = is_string($entity['error_description'] ?? null) ? $entity['error_description'] : null;

            return $this->fail($payment, $reason) ? 'failed' : 'ignored';
        }

        // "created" or "authorized": the money is not captured yet; payment.captured will follow.
        return 'waiting';
    }

    /**
     * An online attempt failed. Only a payment still waiting is marked failed; the member may
     * try again in the same checkout, and a later capture still confirms it.
     */
    public function fail(Payment $payment, ?string $reason = null): bool
    {
        return DB::transaction(function () use ($payment, $reason): bool {
            $locked = $this->lock($payment);

            if ($locked->status !== PaymentStatus::Pending) {
                return false;
            }

            $locked->forceFill(['status' => PaymentStatus::Failed, 'note' => $reason !== null ? mb_substr($reason, 0, 250) : null])->save();
            activity('payments')->performedOn($locked)->event('failed')->log("Online payment failed for {$locked->family->family_no}");

            return true;
        });
    }

    /**
     * The treasurer could not find this UPI payment in the bank: the months stay due.
     *
     * @throws PaymentException
     */
    public function reject(Payment $payment, User $by, string $reason): Payment
    {
        return DB::transaction(function () use ($payment, $by, $reason): Payment {
            $locked = $this->lock($payment);

            if ($locked->status !== PaymentStatus::AwaitingConfirmation) {
                throw new PaymentException('Only a payment waiting for the treasurer can be rejected.');
            }

            $locked->forceFill(['status' => PaymentStatus::Rejected, 'note' => $reason, 'cancelled_by' => $by->id, 'cancelled_at' => now()])->save();

            activity('payments')->performedOn($locked)->causedBy($by)
                ->withProperties(['utr' => $locked->upi_ref, 'reason' => $reason])
                ->event('rejected')
                ->log("UPI payment rejected for {$locked->family->family_no}: {$reason}");

            return $locked;
        });
    }

    /**
     * Cancel a receipt: the number stays in the register marked cancelled, the months are due
     * again and a reversing entry goes into the khata.
     *
     * @throws PaymentException
     */
    public function cancel(Payment $payment, User $by, string $reason): Payment
    {
        return DB::transaction(function () use ($payment, $by, $reason): Payment {
            $locked = $this->lock($payment);

            if ($locked->status !== PaymentStatus::Confirmed) {
                throw new PaymentException('Only a paid receipt can be cancelled.');
            }

            $locked->forceFill(['status' => PaymentStatus::Cancelled, 'note' => $reason, 'cancelled_by' => $by->id, 'cancelled_at' => now()])->save();

            // Months go back to due unless another paid receipt covers them.
            $dueIds = $this->dueIds($locked);
            $coveredElsewhere = DB::table('payment_due')
                ->join('payments', 'payments.id', '=', 'payment_due.payment_id')
                ->whereIn('payment_due.due_id', $dueIds)
                ->where('payments.id', '!=', $locked->id)
                ->where('payments.status', PaymentStatus::Confirmed->value)
                ->pluck('payment_due.due_id')
                ->all();

            Due::query()->whereIn('id', array_diff($dueIds, $coveredElsewhere))->where('status', DueStatus::Paid->value)
                ->update(['status' => DueStatus::Due->value, 'updated_at' => now()]);

            $entries = $locked->ledgerEntries()->whereNull('reverses_id')->whereDoesntHave('reversal')->get();

            foreach ($entries as $entry) {
                app(Khata::class)->reverse($entry, $by, "Receipt {$locked->receipt_no} cancelled: {$reason}");
            }

            activity('payments')->performedOn($locked)->causedBy($by)
                ->withProperties(['receipt' => $locked->receipt_no, 'reason' => $reason])
                ->event('cancelled')
                ->log("Receipt {$locked->receipt_no} cancelled: {$reason}");

            return $locked;
        });
    }

    /**
     * The treasurer takes money at the club: the oldest unpaid months first, then months in
     * advance. The receipt is issued at once.
     *
     * @throws PaymentException
     */
    public function recordAtCounter(Family $family, int $months, PaymentMode $mode, ?string $utr, User $by): Payment
    {
        try {
            return DB::transaction(function () use ($family, $months, $mode, $utr, $by): Payment {
                Family::query()->whereKey($family->id)->lockForUpdate()->first();

                $dues = Due::query()->where('family_id', $family->id)->payable()->orderBy('period')->limit($months)->get();
                $advance = $months - $dues->count();

                if ($advance > 0) {
                    $dues = $dues->merge($this->dues->advanceMonths($family, $advance));
                }

                $payment = Payment::query()->create([
                    'family_id' => $family->id,
                    'amount' => (int) $dues->sum('amount'),
                    'mode' => $mode,
                    'upi_ref' => $mode === PaymentMode::Upi ? $utr : null,
                    'status' => PaymentStatus::Pending,
                    'received_by' => $by->id,
                ]);
                $payment->forceFill(['reference' => self::reference($payment)])->save();
                $payment->dues()->attach($dues->modelKeys());

                return $this->confirm($payment, $by);
            });
        } catch (UniqueConstraintViolationException) {
            throw new PaymentException('This UTR is already on another payment. Check the number in your UPI app.');
        }
    }

    /**
     * Checkouts left unfinished for longer than the given minutes are marked expired.
     */
    public function expireStale(int $minutes): int
    {
        // Only Razorpay checkouts: a UPI-to-the-club payment stays open so the member can still
        // type the UTR later (starting a new payment replaces it).
        return Payment::query()
            ->where('gateway', Gateway::Razorpay->value)
            ->where('status', PaymentStatus::Pending->value)
            ->where('created_at', '<', now()->subMinutes($minutes))
            ->update(['status' => PaymentStatus::Expired->value, 'updated_at' => now()]);
    }

    /**
     * Our reference for a payment (Razorpay order receipt, UPI note): GMC-P0000123.
     */
    public static function reference(Payment $payment): string
    {
        return 'GMC-P'.str_pad((string) $payment->id, 7, '0', STR_PAD_LEFT);
    }

    private function lock(Payment $payment): Payment
    {
        return Payment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail();
    }

    /**
     * @return list<int>
     */
    private function dueIds(Payment $payment): array
    {
        return DB::table('payment_due')->where('payment_id', $payment->id)->pluck('due_id')->map(fn ($id): int => (int) $id)->all();
    }

    /**
     * Income in the Club book (cash in hand for cash, bank for UPI and online), and Razorpay's
     * fee as an expense. Razorpay reports the fee in paise including GST; the khata keeps whole
     * rupees, so the fee is rounded to the nearest rupee and the exact figure goes in the note.
     */
    private function writeKhata(Payment $payment, ?User $by): void
    {
        $book = Book::forCode(BookCode::Club);
        $date = ($payment->paid_at ?? now())->toDateString();

        $payment->ledgerEntries()->create([
            'book_id' => $book->id,
            'entry_date' => $date,
            'type' => LedgerType::Income,
            'account' => $payment->mode->account(),
            'head' => LedgerHead::Subscription,
            'amount' => $payment->amount,
            'note' => trim("{$payment->receipt_no} · {$payment->family->family_no}"),
            'recorded_by' => $by?->id,
        ]);

        $fee = $payment->gateway_fee_paise;

        if ($fee !== null && $fee > 0) {
            $rupees = (int) round($fee / 100);
            $tax = $payment->gateway_tax_paise ?? 0;

            if ($rupees > 0) {
                $payment->ledgerEntries()->create([
                    'book_id' => $book->id,
                    'entry_date' => $date,
                    'type' => LedgerType::Expense,
                    'account' => $payment->mode->account(),
                    'head' => LedgerHead::GatewayFee,
                    'amount' => $rupees,
                    'note' => sprintf('Razorpay fee ₹%s incl. GST ₹%s · %s', number_format($fee / 100, 2), number_format($tax / 100, 2), $payment->gateway_payment_id),
                    'recorded_by' => $by?->id,
                ]);
            }
        }
    }
}
