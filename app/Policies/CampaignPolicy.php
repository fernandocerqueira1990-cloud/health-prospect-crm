<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;

class CampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('campaigns.view');
    }

    public function view(User $user, Campaign $campaign): bool
    {
        return $user->hasPermission('campaigns.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('campaigns.create');
    }

    public function update(User $user, Campaign $campaign): bool
    {
        return $user->hasPermission('campaigns.update');
    }

    public function delete(User $user, Campaign $campaign): bool
    {
        return $user->hasPermission('campaigns.delete');
    }
}
