<?php

namespace App\Models;

use App\Contracts\PublishesContent;
use App\Enums\NoticeAudience;
use App\Enums\PublishStatus;
use App\Models\Concerns\Bilingual;
use App\Models\Concerns\LogsChanges;
use App\Models\Concerns\Publishable;
use Database\Factories\NoticeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A notice for the website (everyone) or the member app (members only).
 */
#[Fillable(['slug', 'title', 'title_bn', 'tag', 'tag_bn', 'summary', 'summary_bn', 'body', 'body_bn', 'audience', 'pinned', 'status', 'published_at'])]
class Notice extends Model implements PublishesContent
{
    /** @use HasFactory<NoticeFactory> */
    use Bilingual, HasFactory, LogsChanges, Publishable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'audience' => NoticeAudience::class,
            'pinned' => 'boolean',
            'status' => PublishStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Published notices everyone may read, pinned first, newest next.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function forPublic(Builder $query): void
    {
        $query->published()
            ->where('audience', NoticeAudience::Public->value)
            ->orderByDesc('pinned')
            ->orderByDesc('published_at');
    }

    /**
     * Published notices for signed-in members (public and members-only).
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function forMembers(Builder $query): void
    {
        $query->published()->orderByDesc('pinned')->orderByDesc('published_at');
    }
}
