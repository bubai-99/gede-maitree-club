<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Chanda cash a collector handed to the treasurer: what the receipts say (expected) and what
 * was counted. The cash was already joma in the Club book when the receipts were written; the
 * handover only records who holds it.
 */
#[Fillable(['event_id', 'collector_id', 'received_by', 'expected', 'counted', 'receipts', 'note'])]
class CashHandover extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expected' => 'integer',
            'counted' => 'integer',
            'receipts' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collector_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return HasMany<ChandaEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(ChandaEntry::class, 'handover_id');
    }
}
