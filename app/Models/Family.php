<?php

namespace App\Models;

use App\Enums\DueStatus;
use App\Enums\FamilyStatus;
use App\Models\Concerns\Bilingual;
use Database\Factories\FamilyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A member household. The subscription is per family.
 */
#[Fillable(['family_no', 'head_name', 'head_name_bn', 'address', 'area', 'area_bn', 'phone', 'status', 'joined_on'])]
class Family extends Model
{
    /** @use HasFactory<FamilyFactory> */
    use Bilingual, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => FamilyStatus::class,
            'joined_on' => 'date',
        ];
    }

    /**
     * @return HasMany<Member, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(Member::class)->orderByDesc('is_head')->orderBy('id');
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<Due, $this>
     */
    public function dues(): HasMany
    {
        return $this->hasMany(Due::class)->orderBy('period');
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('paid_at');
    }

    /**
     * Rupees still owed.
     */
    public function outstanding(): int
    {
        return (int) $this->dues()->where('status', DueStatus::Due->value)->sum('amount');
    }
}
