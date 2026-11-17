<?php

namespace App\Models;

use App\Models\Concerns\Bilingual;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A lane or area for door-to-door chanda, with the collector (volunteer) who has its book.
 */
#[Fillable(['event_id', 'name', 'name_bn', 'collector_id', 'target', 'houses', 'visited', 'sort'])]
class ChandaArea extends Model
{
    use Bilingual;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target' => 'integer',
            'houses' => 'integer',
            'visited' => 'integer',
            'sort' => 'integer',
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
     * @return BelongsTo<User, $this>
     */
    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collector_id');
    }

    /**
     * @return HasMany<ChandaEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(ChandaEntry::class);
    }
}
