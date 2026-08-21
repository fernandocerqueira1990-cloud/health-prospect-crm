<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('users.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('users.create');
    }

    public function update(User $user, User $managedUser): bool
    {
        return $user->hasPermission('users.update')
            && (! $managedUser->hasRole(Role::ADMIN_SLUG) || $user->hasRole(Role::ADMIN_SLUG));
    }
}
