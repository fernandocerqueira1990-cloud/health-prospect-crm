<?php

namespace App\Actions\Roles;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EnsureAdministratorRoleAction
{
    public function __construct(private readonly SyncRolePermissionsAction $syncPermissions) {}

    public function execute(): Role
    {
        return DB::transaction(function (): Role {
            foreach (Permission::SLUGS as $slug) {
                Permission::firstOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => Str::headline(str_replace('.', ' ', $slug)),
                        'description' => 'Permite '.str_replace('.', ' ', $slug).'.',
                    ],
                );
            }

            $role = Role::firstOrCreate(
                ['slug' => Role::ADMIN_SLUG],
                [
                    'name' => 'Administrador',
                    'description' => 'Acesso administrativo total.',
                    'active' => true,
                ],
            );

            if (! $role->active) {
                $role->update(['active' => true]);
            }

            $this->syncPermissions->execute($role, Permission::query()->pluck('id')->all());

            return $role->refresh();
        });
    }
}
