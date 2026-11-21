<?php

namespace App\Models;

use App\Contracts\PublishesContent;
use App\Enums\PublishStatus;
use App\Models\Concerns\Bilingual;
use App\Models\Concerns\HasPhoto;
use App\Models\Concerns\LogsChanges;
use App\Models\Concerns\Publishable;
use App\Support\VideoEmbed;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;

/**
 * A news story: cover photo (HasPhoto), text in English and Bengali (Markdown; a video link on a
 * line of its own becomes a player), video links with captions, and a set of photos ("images").
 *
 * @property int $id
 * @property string $slug
 * @property ?int $category_id
 * @property string $title
 * @property ?string $title_bn
 * @property string $summary
 * @property ?string $summary_bn
 * @property ?string $body
 * @property ?string $body_bn
 * @property ?list<array{url: string, caption?: ?string}> $videos
 * @property bool $featured
 * @property ?int $author_id
 * @property PublishStatus $status
 * @property ?Carbon $published_at
 */
#[Fillable(['slug', 'category_id', 'title', 'title_bn', 'summary', 'summary_bn', 'body', 'body_bn', 'videos', 'featured', 'photo', 'author_id', 'status', 'published_at'])]
class NewsPost extends Model implements HasMedia, PublishesContent
{
    use Bilingual, LogsChanges, Publishable;
    use HasPhoto {
        HasPhoto::registerMediaCollections as registerPhotoCollection;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'videos' => 'array',
            'featured' => 'boolean',
            'status' => PublishStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function registerMediaCollections(): void
    {
        $this->registerPhotoCollection();
        $this->addMediaCollection('images')->acceptsMimeTypes(self::PHOTO_MIMES);
    }

    /**
     * The video links as players, with their captions.
     *
     * @return list<array{embed: VideoEmbed, caption: ?string}>
     */
    public function embeds(): array
    {
        $out = [];
        foreach ($this->videos ?? [] as $video) {
            $embed = VideoEmbed::from($video['url']);
            if ($embed !== null) {
                $out[] = ['embed' => $embed, 'caption' => $video['caption'] ?? null];
            }
        }

        return $out;
    }

    /**
     * Minutes to read, roughly (200 words a minute).
     */
    public function readingMinutes(): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags((string) $this->tr('body'))) / 200));
    }

    /**
     * @return BelongsTo<NewsCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(NewsCategory::class, 'category_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
