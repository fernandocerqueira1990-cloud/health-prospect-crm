<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;

class ActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('activities.view');
    }

    public function view(User $user, Activity $activity): bool
    {
        return $user->hasPermission('activities.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('activities.create');
    }

    public function update(User $user, Activity $activity): bool
    {
        return $user->hasPermission('activities.update');
    }

    public function delete(User $user, Activity $activity): bool
    {
        return $user->hasPermission('activities.delete');
    }
}
