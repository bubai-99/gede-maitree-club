<?php

namespace App\Models;

use App\Enums\PublishStatus;
use App\Models\Concerns\Bilingual;
use App\Models\Concerns\HasPhoto;
use App\Models\Concerns\LogsChanges;
use App\Models\Concerns\Publishable;
use Database\Factories\CommitteeMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;

/**
 * A committee member shown on the About page.
 */
#[Fillable(['name', 'name_bn', 'role', 'role_bn', 'phone', 'photo', 'since', 'sort', 'status', 'published_at'])]
class CommitteeMember extends Model implements HasMedia
{
    /** @use HasFactory<CommitteeMemberFactory> */
    use Bilingual, HasFactory, HasPhoto, LogsChanges, Publishable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'since' => 'integer',
            'sort' => 'integer',
            'status' => PublishStatus::class,
            'published_at' => 'datetime',
        ];
    }

    /**
     * Initials for the avatar when there is no photo.
     */
    public function initials(): string
    {
        $words = preg_split('/\s+/u', trim((string) $this->tr('name'))) ?: [];

        return mb_strtoupper(implode('', array_map(fn (string $w): string => mb_substr($w, 0, 1), array_slice($words, 0, 2))));
    }
}
