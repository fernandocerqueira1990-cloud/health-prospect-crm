<?php

namespace Tests\Concerns;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

trait InteractsWithRbac
{
    protected function seedRbac(): void
    {
        $this->seed(DatabaseSeeder::class);
    }

    protected function admin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'admin')->firstOrFail());

        return $user;
    }

    protected function userWithPermission(string $slug): User
    {
        $permission = Permission::where('slug', $slug)->firstOrFail();
        $role = Role::factory()->create();
        $role->permissions()->attach($permission);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }
}
