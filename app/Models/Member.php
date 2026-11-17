<?php

namespace App\Models;

use App\Models\Concerns\Bilingual;
use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A person in a member family (each gets an ID card).
 */
#[Fillable(['family_id', 'name', 'name_bn', 'relation', 'relation_bn', 'is_head', 'dob', 'phone', 'blood_group', 'member_no'])]
class Member extends Model
{
    /** @use HasFactory<MemberFactory> */
    use Bilingual, HasFactory;

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
}
