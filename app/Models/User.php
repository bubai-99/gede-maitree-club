<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * One table for everyone who signs in: family members (family_id set) and
 * committee members (is_committee, with roles). A person can be both.
 */
#[Fillable(['name', 'email', 'phone', 'password', 'locale', 'family_id', 'is_committee'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_committee' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * @return BelongsTo<Family, $this>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * Belongs to a member family, so may use the member app.
     */
    public function isMember(): bool
    {
        return $this->family_id !== null;
    }

    /**
     * On the committee with at least one committee role, so may use the console.
     */
    public function isCommitteeMember(): bool
    {
        return $this->is_committee
            && $this->hasAnyRole(array_map(fn (Role $role): string => $role->value, Role::committee()));
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::Admin->value);
    }

    /**
     * The person's committee roles in the page's language, e.g. "Treasurer, Website editor".
     */
    public function roleLabels(): string
    {
        return $this->getRoleNames()
            ->map(fn (string $name): string => Role::tryFrom($name)?->label() ?? $name)
            ->implode(', ');
    }

    /**
     * Mobile numbers are kept as the last 10 digits.
     */
    public static function normalisePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        return substr($digits, -10);
    }
}
