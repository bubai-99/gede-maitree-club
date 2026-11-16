<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A member's gate pass for an event (checked by the scanner).
 */
#[Fillable(['event_id', 'member_id', 'qr_token', 'used_at', 'scanned_by'])]
class Pass extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['used_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
