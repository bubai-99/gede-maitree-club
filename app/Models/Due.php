<?php

namespace App\Models;

use App\Enums\DueStatus;
use App\Enums\PaymentStatus;
use Database\Factories\DueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * One month's subscription for a family (period = first day of the month).
 */
#[Fillable(['family_id', 'period', 'amount', 'status', 'waived_reason', 'waived_by', 'waived_at'])]
class Due extends Model
{
    /** @use HasFactory<DueFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period' => 'date',
            'amount' => 'integer',
            'status' => DueStatus::class,
            'waived_at' => 'datetime',
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
    public function waiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'waived_by');
    }

    /**
     * @return BelongsToMany<Payment, $this>
     */
    public function payments(): BelongsToMany
    {
        return $this->belongsToMany(Payment::class, 'payment_due');
    }

    /**
     * Unpaid months that are not already waiting for the treasurer to confirm a UPI payment.
     *
     * @param  Builder<self>  $query
     */
    public function scopePayable(Builder $query): void
    {
        $query->where('status', DueStatus::Due->value)
            ->whereDoesntHave('payments', fn (Builder $q) => $q->where('status', PaymentStatus::AwaitingConfirmation->value));
    }
}
