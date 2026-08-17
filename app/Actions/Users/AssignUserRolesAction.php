<?php

namespace App\Actions\Users;

use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class AssignUserRolesAction
{
    public function __construct(
        private readonly EnsureActiveAdministratorRemainsAction $ensureActiveAdministratorRemains,
        private readonly AuditService $audit,
    ) {}

    /** @param array<int, int|string> $roleIds */
    public function execute(User $user, array $roleIds): void
    {
        DB::transaction(function () use ($user, $roleIds): void {
            $this->ensureActiveAdministratorRemains->execute($user, $user->active, $roleIds);
            $changes = $user->roles()->sync($roleIds);

            foreach ($changes['attached'] as $roleId) {
                $role = Role::find($roleId);
                $this->audit->record('user_role_attached', $user, after: ['role_id' => $roleId, 'role' => $role?->slug]);
            }

            foreach ($changes['detached'] as $roleId) {
                $role = Role::find($roleId);
                $this->audit->record('user_role_detached', $user, before: ['role_id' => $roleId, 'role' => $role?->slug]);
            }
        });
    }
}
