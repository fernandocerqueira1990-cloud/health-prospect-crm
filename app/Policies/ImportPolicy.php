<?php

namespace App\Policies;

use App\Models\DataImport;
use App\Models\User;

class ImportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('imports.view');
    }

    public function view(User $user, DataImport $dataImport): bool
    {
        return $user->hasPermission('imports.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('imports.create');
    }

    public function update(User $user, DataImport $dataImport): bool
    {
        return $user->hasPermission('imports.update');
    }

    public function delete(User $user, DataImport $dataImport): bool
    {
        return $user->hasPermission('imports.delete');
    }
}
