<?php

namespace App\Actions\Roles;

use App\Models\Permission;
use App\Models\Role;
use App\Services\AuditService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

class SyncRolePermissionsAction
{
    public function __construct(private readonly AuditService $audit) {}

    /** @param array<int, int|string> $permissionIds */
    public function execute(Role $role, array $permissionIds): void
    {
        $this->ensureActorCanSync($role, $permissionIds);

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

    /** @param array<int, int|string> $permissionIds */
    private function ensureActorCanSync(Role $role, array $permissionIds): void
    {
        $actor = Auth::user();

        if (! $actor || $actor->hasRole(Role::ADMIN_SLUG)) {
            return;
        }

        $requestsAdministrativePermission = Permission::query()
            ->whereKey(array_map('intval', $permissionIds))
            ->whereIn('slug', Permission::ADMINISTRATIVE_SLUGS)
            ->exists();

        if ($role->isAdministrative() || $requestsAdministrativePermission) {
            throw new AuthorizationException('Somente administradores podem sincronizar permissions administrativas.');
        }
    }
}
