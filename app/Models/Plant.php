<?php

namespace App\Models;

use App\Contracts\PublishesContent;
use App\Enums\PublishStatus;
use App\Models\Concerns\Bilingual;
use App\Models\Concerns\HasPhoto;
use App\Models\Concerns\LogsChanges;
use App\Models\Concerns\Publishable;
use App\Support\Photo;
use App\Support\PlantLists;
use App\Support\VideoEmbed;
use Database\Factories\PlantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;

/**
 * A plant in the Maitree Nursery catalogue.
 */
#[Fillable([
    'slug', 'name', 'name_bn', 'category', 'pot', 'pot_bn', 'price', 'member_price', 'stock', 'is_new',
    'light', 'water', 'about', 'about_bn', 'tip', 'tip_bn', 'details', 'details_bn', 'videos', 'photo', 'gallery',
    'active', 'sort', 'status', 'published_at',
])]
class Plant extends Model implements HasMedia, PublishesContent
{
    /** @use HasFactory<PlantFactory> */
    use Bilingual, HasFactory, LogsChanges, Publishable;

    use HasPhoto {
        HasPhoto::registerMediaCollections as registerPhotoCollection;
    }

    /**
     * The plant's type, sun and water in the page's language (Settings → Plant lists).
     */
    public function categoryName(): string
    {
        return app(PlantLists::class)->label('category', $this->category) ?? (string) $this->category;
    }

    public function lightLabel(): ?string
    {
        return app(PlantLists::class)->label('light', $this->light);
    }

    public function waterLabel(): ?string
    {
        return app(PlantLists::class)->label('water', $this->water);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'member_price' => 'integer',
            'stock' => 'integer',
            'is_new' => 'boolean',
            'active' => 'boolean',
            'sort' => 'integer',
            'status' => PublishStatus::class,
            'published_at' => 'datetime',
            'videos' => 'array',
            'gallery' => 'array',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->registerPhotoCollection();
        $this->addMediaCollection('images')->acceptsMimeTypes(self::PHOTO_MIMES);
    }

    /**
     * Every photo of the plant for the details panel, the main one first: the uploaded photos, or
     * (until the club uploads its own) the sample photos.
     *
     * @return list<array{url: string, thumb: string}>
     */
    public function photos(): array
    {
        $out = [];
        if ($cover = $this->photoUrl(1200)) {
            $out[] = ['url' => $cover, 'thumb' => (string) $this->photoUrl(200)];
        }
        foreach ($this->getMedia('images') as $media) {
            $out[] = ['url' => $media->getUrl(), 'thumb' => $media->getUrl()];
        }
        if ($this->getMedia('images')->isEmpty()) {
            foreach ($this->gallery ?? [] as $key) {
                if (Photo::exists($key)) {
                    $out[] = ['url' => Photo::url($key, 1200), 'thumb' => Photo::url($key, 200)];
                }
            }
        }

        return $out;
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
            $embed = VideoEmbed::from((string) ($video['url'] ?? ''));
            if ($embed !== null) {
                $out[] = ['embed' => $embed, 'caption' => $video['caption'] ?? null];
            }
        }

        return $out;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function inStock(): bool
    {
        return $this->active && $this->stock > 0;
    }

    /**
     * @return HasMany<StockMove, $this>
     */
    public function stockMoves(): HasMany
    {
        return $this->hasMany(StockMove::class);
    }
}
