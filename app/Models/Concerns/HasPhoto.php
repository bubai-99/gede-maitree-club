<?php

namespace App\Models\Concerns;

use App\Support\Photo;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * One photo per record: an uploaded image (spatie/laravel-medialibrary, "photo" collection)
 * or, until the club uploads its own, a sample photo key from config/photos.php.
 *
 * @property string|null $photo
 */
trait HasPhoto
{
    use InteractsWithMedia;

    /** Images the console accepts. */
    public const PHOTO_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')
            ->singleFile()
            ->acceptsMimeTypes(self::PHOTO_MIMES);
    }

    public function hasUploadedPhoto(): bool
    {
        return $this->getFirstMedia('photo') !== null;
    }

    public function photoUrl(int $width = 1200): ?string
    {
        $media = $this->getFirstMedia('photo');

        if ($media !== null) {
            return $media->getUrl();
        }

        return Photo::exists($this->photo) ? Photo::url((string) $this->photo, $width) : null;
    }

    /**
     * The sample photo key, when no photo has been uploaded (for the Unsplash credit).
     */
    public function samplePhotoKey(): ?string
    {
        return ! $this->hasUploadedPhoto() && Photo::exists($this->photo) ? $this->photo : null;
    }
}
