<?php

namespace App\Models;

use App\Contracts\PublishesContent;
use App\Enums\PublishStatus;
use App\Enums\ReviewKind;
use App\Models\Concerns\Bilingual;
use App\Models\Concerns\LogsChanges;
use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A customer's review of the serving team or the nursery (shown on the website once published).
 *
 * @property int $id
 * @property ReviewKind $kind
 * @property int $rating 1–5
 * @property string $body
 * @property ?string $body_bn
 * @property string $who
 * @property ?string $who_bn
 * @property ?string $what
 * @property ?string $what_bn
 * @property ?Carbon $reviewed_on
 * @property int $sort
 * @property PublishStatus $status
 * @property ?Carbon $published_at
 */
#[Fillable(['kind', 'rating', 'body', 'body_bn', 'who', 'who_bn', 'what', 'what_bn', 'reviewed_on', 'sort', 'status', 'published_at'])]
class Review extends Model implements PublishesContent
{
    use Bilingual, LogsChanges, Publishable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => ReviewKind::class,
            'rating' => 'integer',
            'reviewed_on' => 'date',
            'sort' => 'integer',
            'status' => PublishStatus::class,
            'published_at' => 'datetime',
        ];
    }
}
