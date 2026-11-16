<?php

namespace App\Policies;

use App\Models\Family;
use App\Models\User;

class FamilyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('members.manage');
    }

    /**
     * A member sees their own family; the treasurer and admin see every family.
     */
    public function view(User $user, Family $family): bool
    {
        return $user->family_id === $family->id || $user->can('members.manage');
    }

    public function update(User $user, Family $family): bool
    {
        return $user->can('members.manage');
    }
}
