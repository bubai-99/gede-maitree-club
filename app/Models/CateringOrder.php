<?php

namespace App\Models;

use App\Enums\CateringOrderStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A booking of the serving team: enquiry, quote, advance, served, settled.
 */
#[Fillable(['customer_name', 'phone', 'is_member', 'occasion', 'event_date', 'guests', 'package_id', 'venue', 'quoted_amount', 'status'])]
class CateringOrder extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_member' => 'boolean',
            'event_date' => 'date',
            'guests' => 'integer',
            'quoted_amount' => 'integer',
            'status' => CateringOrderStatus::class,
        ];
    }

    /**
     * @return BelongsTo<CateringPackage, $this>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(CateringPackage::class, 'package_id');
    }

    /**
     * @return HasMany<CateringRosterEntry, $this>
     */
    public function roster(): HasMany
    {
        return $this->hasMany(CateringRosterEntry::class, 'order_id');
    }

    /**
     * @return HasMany<CateringPayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(CateringPayment::class, 'order_id');
    }
}
