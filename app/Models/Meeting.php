<?php

namespace App\Models;

use App\Enums\MeetingKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A committee meeting or the AGM.
 */
#[Fillable(['kind', 'held_on', 'minutes', 'attendees'])]
class Meeting extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => MeetingKind::class,
            'held_on' => 'date',
            'attendees' => 'array',
        ];
    }

    /**
     * @return HasMany<Poll, $this>
     */
    public function polls(): HasMany
    {
        return $this->hasMany(Poll::class);
    }
}
