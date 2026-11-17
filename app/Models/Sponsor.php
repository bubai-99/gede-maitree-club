<?php

namespace App\Models;

use App\Enums\SponsorStatus;
use App\Models\Concerns\Bilingual;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A sponsor of an event and what the club gives in return. When the money arrives it gets a
 * receipt in the chanda series (a ChandaEntry with sponsor_id) and goes into the Club book.
 *
 * @property SponsorStatus $status
 */
#[Fillable(['event_id', 'name', 'name_bn', 'amount', 'deliverable', 'deliverable_bn', 'status'])]
class Sponsor extends Model
{
    use Bilingual;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'status' => SponsorStatus::class,
        ];
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
    public function receipts(): HasMany
    {
        return $this->hasMany(ChandaEntry::class);
    }

    public function isReceived(): bool
    {
        return $this->status === SponsorStatus::Received;
    }
}
