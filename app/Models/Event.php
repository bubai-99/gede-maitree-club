<?php

namespace App\Models;

use App\Enums\EventKind;
use App\Enums\PublishStatus;
use App\Models\Concerns\Bilingual;
use App\Models\Concerns\HasPhoto;
use App\Models\Concerns\LogsChanges;
use App\Models\Concerns\Publishable;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;

/**
 * A club event (puja, sports, service, members outing), edited in the website CMS.
 *
 * schedule: list of {time, time_bn, what, what_bn}; bring: list of {text, text_bn}.
 */
#[Fillable([
    'slug', 'title', 'title_bn', 'kind', 'starts_at', 'time_label', 'time_label_bn', 'place', 'place_bn',
    'pass_required', 'contact_role', 'chanda_box', 'about', 'about_bn', 'schedule', 'bring', 'photo',
    'status', 'published_at',
])]
class Event extends Model implements HasMedia
{
    /** @use HasFactory<EventFactory> */
    use Bilingual, HasFactory, HasPhoto, LogsChanges, Publishable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => EventKind::class,
            'starts_at' => 'datetime',
            'pass_required' => 'boolean',
            'chanda_box' => 'boolean',
            'schedule' => 'array',
            'bring' => 'array',
            'status' => PublishStatus::class,
            'published_at' => 'datetime',
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
     * @return HasMany<ChandaEntry, $this>
     */
    public function chandaEntries(): HasMany
    {
        return $this->hasMany(ChandaEntry::class);
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
}
