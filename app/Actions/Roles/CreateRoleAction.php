<?php

namespace App\Actions\Roles;

use App\Models\Role;
use App\Services\AuditService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateRoleAction
{
    public function __construct(
        private readonly SyncRolePermissionsAction $syncPermissions,
        private readonly AuditService $audit,
    ) {}

    public function execute(array $data): Role
    {
        if (($data['slug'] ?? null) === Role::ADMIN_SLUG) {
            throw ValidationException::withMessages([
                'slug' => 'O slug admin é reservado para a role Administrador.',
            ]);
        }

        return DB::transaction(function () use ($data): Role {
            $role = Role::create(Arr::only($data, ['name', 'slug', 'description', 'active']));
            $this->audit->record('role_created', $role, after: $role->only(['name', 'slug', 'description', 'active']));
            $this->syncPermissions->execute($role, $data['permission_ids'] ?? []);

            return $role;
        });
    }
}
