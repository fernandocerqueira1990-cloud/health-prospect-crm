<?php

namespace Tests\Feature\Rbac;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\Concerns\InteractsWithRbac;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use InteractsWithRbac, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRbac();
    }

    public function test_administrator_has_access_to_every_registered_permission(): void
    {
        $admin = $this->admin();

        foreach (Permission::SLUGS as $slug) {
            $this->assertTrue(Gate::forUser($admin)->allows($slug), $slug);
        }
    }

    public function test_user_without_permission_receives_forbidden(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_user_with_permission_can_access_protected_resource(): void
    {
        $user = $this->userWithPermission('users.view');

        $this->actingAs($user)->get(route('admin.users.index'))->assertOk();
    }

    public function test_role_can_be_associated_with_permission(): void
    {
        $role = Role::factory()->create();
        $permission = Permission::where('slug', 'users.view')->firstOrFail();
        $role->permissions()->attach($permission);

        $this->assertTrue($role->permissions->contains($permission));
        $this->assertDatabaseHas('permission_role', ['role_id' => $role->id, 'permission_id' => $permission->id]);
    }

    public function test_user_can_be_associated_with_role(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();
        $user->roles()->attach($role);

        $this->assertTrue($user->roles->contains($role));
        $this->assertDatabaseHas('role_user', ['user_id' => $user->id, 'role_id' => $role->id]);
    }

    public function test_rbac_seeders_can_be_reexecuted_without_duplicates(): void
    {
        $roleCount = Role::count();
        $permissionCount = Permission::count();

        $this->seedRbac();

        $this->assertSame($roleCount, Role::count());
        $this->assertSame($permissionCount, Permission::count());
        $this->assertSame($permissionCount, Role::where('slug', 'admin')->firstOrFail()->permissions()->count());
    }
}
