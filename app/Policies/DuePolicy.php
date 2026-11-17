<?php

namespace App\Policies;

use App\Models\User;

/**
 * Monthly dues: the treasurer (fees.manage) and the Admin list them, add a month,
 * waive months and send reminders.
 */
class DuePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('fees.manage');
    }

    public function generate(User $user): bool
    {
        return $user->can('fees.manage');
    }

    public function waive(User $user): bool
    {
        return $user->can('fees.manage');
    }

    public function remind(User $user): bool
    {
        return $user->can('fees.manage');
    }
}
