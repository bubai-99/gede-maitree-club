<?php

namespace App\Models;

use App\Enums\PanjikaKind;
use App\Models\Concerns\Bilingual;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A day the committee entered from the club's panjika: a puja (with its tithi), a parbon
 * (sankranti, Poila Boishakh) or a wedding day with its lagna.
 *
 * @property int $id
 * @property Carbon $date
 * @property PanjikaKind $kind
 * @property string $name
 * @property ?string $name_bn
 * @property ?int $tithi 0–29: 0–14 shukla (14 Purnima), 15–29 krishna (29 Amavasya)
 * @property ?string $lagna
 * @property ?string $lagna_bn
 * @property ?int $event_id
 * @property ?int $created_by
 */
#[Fillable(['date', 'kind', 'name', 'name_bn', 'tithi', 'lagna', 'lagna_bn', 'event_id', 'created_by'])]
class PanjikaDay extends Model
{
    use Bilingual;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'kind' => PanjikaKind::class,
            'tithi' => 'integer',
        ];
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeFromToday(Builder $query): void
    {
        $query->where('date', '>=', today()->toDateString());
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
