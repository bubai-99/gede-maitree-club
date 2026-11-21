<?php

namespace App\Models;

use App\Enums\PaymentMode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A counter sale at the nursery: an NS receipt and income in the Nursery book. A member family
 * pays the member price; the difference is the discount. A cancelled sale keeps its number,
 * puts the plants back in stock and reverses the income.
 *
 * @property int $id
 * @property ?string $buyer_name
 * @property ?int $family_id
 * @property int $subtotal
 * @property int $discount
 * @property int $total
 * @property PaymentMode $mode
 * @property ?string $upi_ref
 * @property ?string $receipt_no
 * @property ?int $recorded_by
 * @property ?int $ledger_entry_id
 * @property ?Carbon $cancelled_at
 * @property ?int $cancelled_by
 * @property ?string $cancel_reason
 * @property ?Carbon $created_at
 */
#[Fillable(['buyer_name', 'family_id', 'subtotal', 'discount', 'total', 'mode', 'upi_ref', 'receipt_no', 'recorded_by'])]
class NurserySale extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'discount' => 'integer',
            'total' => 'integer',
            'mode' => PaymentMode::class,
            'cancelled_at' => 'datetime',
        ];
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    /**
     * @return BelongsTo<Family, $this>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * @return BelongsTo<LedgerEntry, $this>
     */
    public function ledgerEntry(): BelongsTo
    {
        return $this->belongsTo(LedgerEntry::class);
    }

    /**
     * @return HasMany<NurserySaleItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(NurserySaleItem::class, 'sale_id');
    }
}
