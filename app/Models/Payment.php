<?php

namespace App\Models;

use App\Enums\Gateway;
use App\Enums\PaymentMode;
use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Money a family pays: cash at the club, UPI with a UTR the treasurer confirms,
 * or online through Razorpay. Amounts are whole rupees.
 */
#[Fillable([
    'family_id', 'amount', 'mode', 'gateway', 'reference', 'gateway_order_id', 'gateway_payment_id', 'gateway_method',
    'gateway_fee_paise', 'gateway_tax_paise', 'upi_ref', 'received_by', 'receipt_no', 'status', 'note',
    'paid_at', 'submitted_at', 'cancelled_at', 'cancelled_by',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'mode' => PaymentMode::class,
            'gateway' => Gateway::class,
            'status' => PaymentStatus::class,
            'gateway_fee_paise' => 'integer',
            'gateway_tax_paise' => 'integer',
            'paid_at' => 'datetime',
            'submitted_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
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
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * @return BelongsToMany<Due, $this>
     */
    public function dues(): BelongsToMany
    {
        return $this->belongsToMany(Due::class, 'payment_due')->orderBy('period');
    }

    /**
     * @return MorphMany<LedgerEntry, $this>
     */
    public function ledgerEntries(): MorphMany
    {
        return $this->morphMany(LedgerEntry::class, 'source');
    }

    /**
     * Payments with a receipt number (confirmed, or cancelled but kept in the register).
     *
     * @param  Builder<self>  $query
     */
    public function scopeReceipted(Builder $query): void
    {
        $query->whereNotNull('receipt_no');
    }

    public function isPaid(): bool
    {
        return $this->status === PaymentStatus::Confirmed;
    }

    /**
     * "Cash", "UPI" or "Online · Card".
     */
    public function modeLabel(): string
    {
        $label = $this->mode->label();

        if ($this->mode === PaymentMode::Online && $this->gateway_method) {
            $label .= ' · '.$this->methodLabel((string) $this->gateway_method);
        }

        return $label;
    }

    /**
     * Razorpay's payment method (card, netbanking, wallet, emi, upi, paylater).
     */
    private function methodLabel(string $method): string
    {
        return match ($method) {
            'upi' => __('UPI'),
            'card' => __('Card'),
            'netbanking' => __('Netbanking'),
            'wallet' => __('Wallet'),
            'emi' => __('EMI'),
            'paylater' => __('Pay later'),
            default => $method,
        };
    }

    /**
     * Months this payment is for, e.g. "Oct 2026, Nov 2026".
     */
    public function monthsLabel(): string
    {
        return $this->dues->map(fn (Due $due): string => local_date($due->period, 'M Y'))->implode(', ');
    }
}
