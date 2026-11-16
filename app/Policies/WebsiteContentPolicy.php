<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Website CMS content: every committee role may look; only the Admin and the
 * Website editor may change or publish it.
 */
abstract class WebsiteContentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('website.view');
    }

    public function view(User $user, Model $model): bool
    {
        return $user->can('website.view');
    }

    public function create(User $user): bool
    {
        return $user->can('website.edit');
    }

    public function update(User $user, Model $model): bool
    {
        return $user->can('website.edit');
    }

    public function publish(User $user, Model $model): bool
    {
        return $user->can('website.edit');
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->can('website.edit');
    }
}
