<?php

namespace App\Models;

use App\Models\Concerns\Bilingual;
use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * A person in a member family (each gets an ID card). The ID card photo is personal data: it is
 * kept on the private disk and served only to the family and the committee
 * (member.photos.show, admin can see it through the same route).
 */
#[Fillable(['family_id', 'name', 'name_bn', 'relation', 'relation_bn', 'is_head', 'dob', 'phone', 'blood_group', 'member_no'])]
class Member extends Model implements HasMedia
{
    /** @use HasFactory<MemberFactory> */
    use Bilingual, HasFactory, InteractsWithMedia;

    /** Images accepted for the ID card photo. */
    public const PHOTO_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    public const BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_head' => 'boolean',
            'dob' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Family, $this>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function age(): ?int
    {
        return $this->dob ? (int) $this->dob->diffInYears(now()) : null;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')
            ->singleFile()
            ->useDisk('local')
            ->acceptsMimeTypes(self::PHOTO_MIMES);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // 3:4 like the photo box on the card, made at once so the card never waits for a queue.
        $this->addMediaConversion('card')
            ->performOnCollections('photo')
            ->nonQueued()
            ->fit(Fit::Crop, 360, 480)
            ->format('jpg')
            ->quality(85);
    }

    public function hasPhoto(): bool
    {
        return $this->getFirstMedia('photo') !== null;
    }

    /**
     * Address of the card photo (a version number keeps browsers from showing an old one).
     */
    public function photoUrl(): ?string
    {
        $media = $this->getFirstMedia('photo');

        return $media !== null ? route('member.photos.show', ['member' => $this, 'v' => $media->id]) : null;
    }
}
