<?php

namespace App\Models;

use App\Enums\RegistrationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * A family's registration for an event: how many people are coming. One per family and event;
 * cancelling keeps the row (status cancelled) so the family can register again before the cut-off.
 *
 * @property RegistrationStatus $status
 * @property Carbon|null $cancelled_at
 */
#[Fillable(['event_id', 'family_id', 'people', 'status', 'registered_by', 'cancelled_at', 'cancelled_by'])]
class EventRegistration extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'people' => 'integer',
            'status' => RegistrationStatus::class,
            'cancelled_at' => 'datetime',
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
     * @return BelongsTo<Family, $this>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * @return HasOne<Pass, $this>
     */
    public function pass(): HasOne
    {
        return $this->hasOne(Pass::class, 'registration_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function isActive(): bool
    {
        return $this->status === RegistrationStatus::Registered;
    }
}
