<?php

namespace App\Models;

use App\Enums\CateringPaymentKind;
use App\Enums\PaymentMode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Money for a catering order: advance, part payment or final bill (a CT receipt and income in the
 * Catering book), or a refund when a booking is cancelled (an expense, no receipt number).
 *
 * @property int $id
 * @property int $order_id
 * @property int $amount
 * @property CateringPaymentKind $kind
 * @property PaymentMode $mode
 * @property ?string $upi_ref
 * @property ?string $note
 * @property ?Carbon $paid_on
 * @property ?int $received_by
 * @property ?int $ledger_entry_id
 * @property ?string $receipt_no
 * @property ?Carbon $created_at
 */
#[Fillable(['order_id', 'amount', 'kind', 'mode', 'upi_ref', 'note', 'paid_on', 'received_by', 'receipt_no'])]
class CateringPayment extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'kind' => CateringPaymentKind::class,
            'mode' => PaymentMode::class,
            'paid_on' => 'date',
        ];
    }

    public function isRefund(): bool
    {
        return $this->kind === CateringPaymentKind::Refund;
    }

    /**
     * @return BelongsTo<CateringOrder, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(CateringOrder::class, 'order_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * @return BelongsTo<LedgerEntry, $this>
     */
    public function ledgerEntry(): BelongsTo
    {
        return $this->belongsTo(LedgerEntry::class);
    }
}
