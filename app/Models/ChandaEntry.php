<?php

namespace App\Models;

use App\Enums\ChandaMode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A chanda receipt (door-to-door gift, or a sponsor's money) in the CH series, e.g. CH-26-0001.
 * Its income line in the Club book is ledger_entry_id. A cancelled receipt keeps its number
 * (marked cancelled) and its income is reversed in the khata.
 *
 * @property ChandaMode $mode
 * @property Carbon|null $received_at
 * @property Carbon|null $cancelled_at
 */
#[Fillable([
    'event_id', 'chanda_area_id', 'family_id', 'sponsor_id', 'donor_name', 'donor_phone', 'show_name', 'amount', 'mode',
    'upi_ref', 'collected_by', 'recorded_by', 'receipt_no', 'received_at',
])]
class ChandaEntry extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'mode' => ChandaMode::class,
            'show_name' => 'boolean',
            'received_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Receipts that still count (not cancelled).
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function valid(Builder $query): void
    {
        $query->whereNull($this->qualifyColumn('cancelled_at'));
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<ChandaArea, $this>
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(ChandaArea::class, 'chanda_area_id');
    }

    /**
     * @return BelongsTo<Family, $this>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * @return BelongsTo<Sponsor, $this>
     */
    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(Sponsor::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
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
     * @return BelongsTo<CashHandover, $this>
     */
    public function handover(): BelongsTo
    {
        return $this->belongsTo(CashHandover::class, 'handover_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
