<?php

namespace App\Models\Concerns;

use App\Enums\PublishStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Website content edited in the console: saved as a draft, shown on the
 * public site only once published.
 *
 * @property PublishStatus $status
 * @property Carbon|null $published_at
 */
trait Publishable
{
    /**
     * Only content that is live on the website.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where($this->qualifyColumn('status'), PublishStatus::Published->value);
    }

    public function isPublished(): bool
    {
        return $this->status === PublishStatus::Published;
    }

    public function publish(): void
    {
        $this->forceFill([
            'status' => PublishStatus::Published,
            'published_at' => $this->published_at ?? now(),
        ])->save();
    }

    public function unpublish(): void
    {
        $this->forceFill(['status' => PublishStatus::Draft])->save();
    }
}
