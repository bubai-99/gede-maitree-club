<?php

namespace App\Models;

use App\Enums\PaymentMode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A counter sale at the nursery gate.
 */
#[Fillable(['buyer_name', 'member_id', 'total', 'mode', 'receipt_no'])]
class NurserySale extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total' => 'integer',
            'mode' => PaymentMode::class,
        ];
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * @return HasMany<NurserySaleItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(NurserySaleItem::class, 'sale_id');
    }
}
