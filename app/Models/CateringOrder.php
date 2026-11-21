<?php

namespace App\Models;

use App\Enums\CateringOrderStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * A booking of the serving team: enquiry, quote, advance, served, settled (or lost / cancelled).
 * The club serves only; the customer arranges the food. The per-guest rate is fixed at the quote.
 *
 * @property int $id
 * @property ?int $enquiry_id
 * @property string $customer_name
 * @property string $phone
 * @property bool $is_member
 * @property ?string $occasion
 * @property Carbon $event_date
 * @property int $guests
 * @property ?int $package_id
 * @property ?int $rate
 * @property ?string $venue
 * @property ?int $quoted_amount
 * @property ?string $quote_note
 * @property ?Carbon $quote_valid_until
 * @property CateringOrderStatus $status
 * @property ?Carbon $quoted_at
 * @property ?Carbon $served_at
 * @property ?Carbon $settled_at
 * @property ?Carbon $closed_at
 * @property ?string $closed_reason
 * @property ?int $closed_by
 * @property ?Carbon $wages_paid_at
 * @property ?int $created_by
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
#[Fillable(['enquiry_id', 'customer_name', 'phone', 'is_member', 'occasion', 'event_date', 'guests', 'package_id', 'rate', 'venue', 'quoted_amount', 'quote_note', 'quote_valid_until', 'status', 'created_by'])]
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
            'rate' => 'integer',
            'quoted_amount' => 'integer',
            'quote_valid_until' => 'date',
            'status' => CateringOrderStatus::class,
            'quoted_at' => 'datetime',
            'served_at' => 'datetime',
            'settled_at' => 'datetime',
            'closed_at' => 'datetime',
            'wages_paid_at' => 'datetime',
        ];
    }

    /**
     * Order number shown on the board and receipts, e.g. CO-0042.
     */
    public function number(): string
    {
        return sprintf('CO-%04d', $this->id);
    }

    /**
     * The bill: guests at the fixed rate (0 until a package is chosen).
     */
    public function total(): int
    {
        return $this->guests * (int) $this->rate;
    }

    /**
     * Advance asked for with the quote: 20% of the total, in whole rupees.
     */
    public function advanceDue(): int
    {
        return (int) round($this->total() * (int) config('club.catering_advance_percent') / 100);
    }

    /**
     * @return BelongsTo<CateringPackage, $this>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(CateringPackage::class, 'package_id');
    }

    /**
     * @return BelongsTo<Enquiry, $this>
     */
    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(Enquiry::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
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

    /**
     * Expenses entered against this order in the Catering book (wages, plates, transport...).
     *
     * @return MorphMany<LedgerEntry, $this>
     */
    public function expenses(): MorphMany
    {
        return $this->morphMany(LedgerEntry::class, 'source');
    }
}
