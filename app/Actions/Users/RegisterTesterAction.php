<?php

namespace App\Actions\Users;

use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class RegisterTesterAction
{
    public function __construct(private readonly AuditService $audit) {}

    public function execute(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $testerRole = Role::query()
                ->where('slug', Role::TESTER_SLUG)
                ->where('active', true)
                ->firstOrFail();

            $user = User::create([
                ...Arr::only($data, ['name', 'email', 'password']),
                'active' => true,
            ]);
            $user->roles()->sync([$testerRole->getKey()]);

            $this->audit->record(
                'user_registered',
                $user,
                after: $user->only(['name', 'email', 'active']),
                user: $user,
            );

            return $user;
        });
    }
}
