<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CreateUserAction
{
    public function __construct(
        private readonly AssignUserRolesAction $assignRoles,
        private readonly AuditService $audit,
    ) {}

    public function execute(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::create(Arr::only($data, ['name', 'email', 'password', 'active']));
            $this->audit->record('user_created', $user, after: $user->only(['name', 'email', 'active']));
            $this->assignRoles->execute($user, $data['role_ids'] ?? []);

            return $user;
        });
    }
}
