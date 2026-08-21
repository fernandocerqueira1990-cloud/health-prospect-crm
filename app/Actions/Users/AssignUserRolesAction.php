<?php

namespace App\Actions\Users;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignUserRolesAction
{
    public function __construct(
        private readonly EnsureActiveAdministratorRemainsAction $ensureActiveAdministratorRemains,
        private readonly AuditService $audit,
    ) {}

    /** @param array<int, int|string> $roleIds */
    public function execute(User $user, array $roleIds): void
    {
        $this->ensureAssignmentIsAllowed($roleIds);

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

    /** @param array<int, int|string> $roleIds */
    private function ensureAssignmentIsAllowed(array $roleIds): void
    {
        $roles = Role::query()->whereKey(array_map('intval', $roleIds))->with('permissions:id,slug')->get();
        $containsAdministrativeRole = $roles->contains(
            fn (Role $role): bool => $role->slug === Role::ADMIN_SLUG
                || $role->permissions->contains(fn ($permission): bool => in_array($permission->slug, Permission::ADMINISTRATIVE_SLUGS, true)),
        );

        $actor = Auth::user();
        if ($actor && ! $actor->hasRole(Role::ADMIN_SLUG) && $containsAdministrativeRole) {
            throw new AuthorizationException('Somente administradores podem atribuir roles administrativas.');
        }

        if ($roles->contains('slug', Role::TESTER_SLUG) && $containsAdministrativeRole) {
            throw ValidationException::withMessages([
                'role_ids' => 'A role Tester não pode ser combinada com uma role administrativa.',
            ]);
        }
    }
}
