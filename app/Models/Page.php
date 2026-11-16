<?php

namespace App\Models;

use App\Enums\PublishStatus;
use App\Models\Concerns\Bilingual;
use App\Models\Concerns\HasPhoto;
use App\Models\Concerns\LogsChanges;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;

/**
 * Editable text for a website page (CMS). "blocks" is what the public site shows;
 * "draft" is the working copy in the console until it is published.
 *
 * Blocks: {banner: {eyebrow, eyebrow_bn, title, title_bn, lead, lead_bn, photo}, sections: [{heading, heading_bn, text, text_bn}]}
 */
#[Fillable(['key', 'title', 'title_bn', 'blocks', 'draft', 'status', 'published_at'])]
class Page extends Model implements HasMedia
{
    use Bilingual, HasPhoto, LogsChanges;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'blocks' => 'array',
            'draft' => 'array',
            'status' => PublishStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'key';
    }

    /**
     * The published page for a key, if there is one.
     */
    public static function live(string $key): ?self
    {
        return self::query()->where('key', $key)->whereNotNull('blocks')->first();
    }

    /**
     * A published text block in the page's language, e.g. block('banner.title').
     */
    public function block(string $path, ?string $default = null): ?string
    {
        $bengali = app()->getLocale() === 'bn' ? data_get($this->blocks, $path.'_bn') : null;
        $value = is_string($bengali) && $bengali !== '' ? $bengali : data_get($this->blocks, $path);

        return is_string($value) && $value !== '' ? $value : $default;
    }

    public function hasUnpublishedChanges(): bool
    {
        return $this->draft !== null && $this->draft !== $this->blocks;
    }

    /**
     * Make the draft live.
     */
    public function publishDraft(): void
    {
        $this->forceFill([
            'blocks' => $this->draft ?? $this->blocks,
            'status' => PublishStatus::Published,
            'published_at' => now(),
        ])->save();
    }
}
