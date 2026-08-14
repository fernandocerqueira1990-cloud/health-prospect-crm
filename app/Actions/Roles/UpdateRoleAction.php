<?php

namespace App\Actions\Roles;

use App\Models\Role;
use App\Services\AuditService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateRoleAction
{
    public function __construct(
        private readonly SyncRolePermissionsAction $syncPermissions,
        private readonly AuditService $audit,
    ) {}

    public function execute(Role $role, array $data): Role
    {
        $this->ensureAdministratorInvariant($role, $data);

        return DB::transaction(function () use ($role, $data): Role {
            $before = $role->only(['name', 'slug', 'description', 'active']);
            $role->update(Arr::only($data, ['name', 'slug', 'description', 'active']));
            $this->audit->record('role_updated', $role, $before, $role->only(['name', 'slug', 'description', 'active']));

            if (array_key_exists('permission_ids', $data)) {
                $this->syncPermissions->execute($role, $data['permission_ids']);
            }

            return $role->refresh();
        });
    }

    private function ensureAdministratorInvariant(Role $role, array $data): void
    {
        $originalSlug = (string) $role->getRawOriginal('slug');
        $requestedSlug = (string) ($data['slug'] ?? $role->slug);
        $requestedActive = array_key_exists('active', $data) ? (bool) $data['active'] : $role->active;

        if ($originalSlug === Role::ADMIN_SLUG
            && ($requestedSlug !== Role::ADMIN_SLUG || ! $requestedActive)) {
            throw ValidationException::withMessages([
                'slug' => 'A role Administrador deve manter o slug admin e permanecer ativa.',
            ]);
        }

        if ($originalSlug !== Role::ADMIN_SLUG && $requestedSlug === Role::ADMIN_SLUG) {
            throw ValidationException::withMessages([
                'slug' => 'O slug admin é reservado para a role Administrador.',
            ]);
        }
    }
}
