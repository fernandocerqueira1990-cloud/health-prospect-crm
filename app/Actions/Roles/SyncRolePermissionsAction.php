<?php

namespace App\Actions\Roles;

use App\Models\Permission;
use App\Models\Role;
use App\Services\AuditService;

class SyncRolePermissionsAction
{
    public function __construct(private readonly AuditService $audit) {}

    /** @param array<int, int|string> $permissionIds */
    public function execute(Role $role, array $permissionIds): void
    {
        if ($role->slug === Role::ADMIN_SLUG) {
            $permissionIds = Permission::query()->pluck('id')->all();
        }

        $changes = $role->permissions()->sync($permissionIds);

        foreach ($changes['attached'] as $permissionId) {
            $permission = Permission::find($permissionId);
            $this->audit->record('permission_attached', $role, after: ['permission_id' => $permissionId, 'permission' => $permission?->slug]);
        }

        foreach ($changes['detached'] as $permissionId) {
            $permission = Permission::find($permissionId);
            $this->audit->record('permission_detached', $role, before: ['permission_id' => $permissionId, 'permission' => $permission?->slug]);
        }
    }
}
