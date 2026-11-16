<?php

namespace App\Models;

use App\Enums\DueStatus;
use Database\Factories\DueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * One month's subscription for a family (period = first day of the month).
 */
#[Fillable(['family_id', 'period', 'amount', 'status'])]
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
     * @return BelongsToMany<Payment, $this>
     */
    public function payments(): BelongsToMany
    {
        return $this->belongsToMany(Payment::class, 'payment_due');
    }
}
