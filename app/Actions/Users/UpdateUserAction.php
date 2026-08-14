<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateUserAction
{
    public function __construct(
        private readonly AssignUserRolesAction $assignRoles,
        private readonly EnsureActiveAdministratorRemainsAction $ensureActiveAdministratorRemains,
        private readonly AuditService $audit,
    ) {}

    public function execute(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $roleIds = array_key_exists('role_ids', $data)
                ? $data['role_ids']
                : $user->roles()->pluck('roles.id')->all();
            $willBeActive = array_key_exists('active', $data) ? (bool) $data['active'] : $user->active;

            $this->ensureActiveAdministratorRemains->execute($user, $willBeActive, $roleIds);

            $before = $user->only(['name', 'email', 'active']);
            $attributes = Arr::only($data, ['name', 'email', 'active']);

            if (! empty($data['password'])) {
                $attributes['password'] = $data['password'];
            }

            $user->update($attributes);
            $after = $user->only(['name', 'email', 'active']);
            $this->audit->record('user_updated', $user, $before, $after);

            if ($before['active'] !== $after['active']) {
                $this->audit->record($after['active'] ? 'user_activated' : 'user_deactivated', $user, $before, $after);
            }

            if (array_key_exists('role_ids', $data)) {
                $this->assignRoles->execute($user, $data['role_ids']);
            }

            return $user->refresh();
        });
    }
}
