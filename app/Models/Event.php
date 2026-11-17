<?php

namespace App\Models;

use App\Contracts\PublishesContent;
use App\Enums\EventKind;
use App\Enums\PublishStatus;
use App\Enums\RegistrationStatus;
use App\Models\Concerns\Bilingual;
use App\Models\Concerns\HasPhoto;
use App\Models\Concerns\LogsChanges;
use App\Models\Concerns\Publishable;
use Carbon\CarbonInterface;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;

/**
 * A club event (puja, sports, service, members outing), edited in the website CMS.
 *
 * schedule: list of {time, time_bn, what, what_bn}; bring: list of {text, text_bn};
 * budget: list of {head, head_bn, amount} (whole rupees).
 *
 * Registration: families register in the member app while registration is open and before
 * the cut-off (registration_closes_at, or the start of the event). capacity counts people,
 * family_limit is the most people one family may bring. When pass_required is set each
 * registration gets a gate pass.
 *
 * @property Carbon $starts_at
 * @property Carbon|null $registration_closes_at
 * @property Carbon|null $cancelled_at
 */
#[Fillable([
    'slug', 'title', 'title_bn', 'kind', 'starts_at', 'time_label', 'time_label_bn', 'place', 'place_bn',
    'pass_required', 'registration_open', 'registration_closes_at', 'capacity', 'family_limit', 'members_only',
    'contact_role', 'chanda_box', 'chanda_target', 'chanda_public', 'about', 'about_bn', 'schedule', 'bring', 'budget',
    'photo', 'status', 'published_at',
])]
class Event extends Model implements HasMedia, PublishesContent
{
    /** @use HasFactory<EventFactory> */
    use Bilingual, HasFactory, HasPhoto, LogsChanges, Publishable;

    /** Most people one family may register when the committee sets no limit. */
    public const DEFAULT_FAMILY_LIMIT = 10;

    /** Slugs the member app uses for its own pages under /app/events. */
    public const RESERVED_SLUGS = ['passes', 'past'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => EventKind::class,
            'starts_at' => 'datetime',
            'pass_required' => 'boolean',
            'registration_open' => 'boolean',
            'registration_closes_at' => 'datetime',
            'capacity' => 'integer',
            'family_limit' => 'integer',
            'members_only' => 'boolean',
            'chanda_box' => 'boolean',
            'chanda_target' => 'integer',
            'chanda_public' => 'boolean',
            'schedule' => 'array',
            'bring' => 'array',
            'budget' => 'array',
            'status' => PublishStatus::class,
            'published_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Events from today on, soonest first.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function upcoming(Builder $query): void
    {
        $query->where('starts_at', '>=', now()->startOfDay())->orderBy('starts_at');
    }

    /**
     * Events before today, latest first.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function past(Builder $query): void
    {
        $query->where('starts_at', '<', now()->startOfDay())->orderByDesc('starts_at');
    }

    /**
     * Events that collect chanda (puja chanda book), latest first.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function collectingChanda(Builder $query): void
    {
        $query->where('chanda_box', true)->orderByDesc('starts_at');
    }

    /**
     * @return HasMany<ChandaEntry, $this>
     */
    public function chandaEntries(): HasMany
    {
        return $this->hasMany(ChandaEntry::class);
    }

    /**
     * @return HasMany<ChandaArea, $this>
     */
    public function chandaAreas(): HasMany
    {
        return $this->hasMany(ChandaArea::class)->orderBy('sort')->orderBy('name');
    }

    /**
     * @return HasMany<Sponsor, $this>
     */
    public function sponsors(): HasMany
    {
        return $this->hasMany(Sponsor::class);
    }

    /**
     * @return HasMany<Pass, $this>
     */
    public function passes(): HasMany
    {
        return $this->hasMany(Pass::class);
    }

    /**
     * @return HasMany<EventRegistration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    /**
     * @return HasMany<PassScan, $this>
     */
    public function scans(): HasMany
    {
        return $this->hasMany(PassScan::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    /**
     * Registering, changing and cancelling a registration are allowed until this moment.
     */
    public function cutoff(): CarbonInterface
    {
        $closes = $this->registration_closes_at;

        return $closes !== null && $closes->lessThan($this->starts_at) ? $closes : $this->starts_at;
    }

    /**
     * Families may register (or change or cancel a registration) right now.
     */
    public function acceptsRegistrations(?CarbonInterface $at = null): bool
    {
        return $this->registration_open
            && $this->isPublished()
            && ! $this->isCancelled()
            && ($at ?? now())->lessThan($this->cutoff());
    }

    /**
     * The most people one family may register.
     */
    public function familyLimit(): int
    {
        return $this->family_limit !== null && $this->family_limit > 0 ? $this->family_limit : self::DEFAULT_FAMILY_LIMIT;
    }

    /**
     * People registered so far (cancelled registrations do not count).
     */
    public function registeredPeople(): int
    {
        return (int) $this->registrations()->where('status', RegistrationStatus::Registered->value)->sum('people');
    }

    /**
     * Seats still free, or null when there is no limit.
     */
    public function seatsLeft(): ?int
    {
        return $this->capacity !== null ? max(0, $this->capacity - $this->registeredPeople()) : null;
    }

    /**
     * Total of the budget heads, in rupees.
     */
    public function budgetTotal(): int
    {
        return (int) array_sum(array_map(fn (array $line): int => (int) ($line['amount'] ?? 0), $this->budget ?? []));
    }
}
