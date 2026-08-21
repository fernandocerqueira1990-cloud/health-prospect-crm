<?php

namespace Tests\Feature\Admin;

use App\Actions\Roles\SyncRolePermissionsAction;
use App\Actions\Roles\UpdateRoleAction;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\InteractsWithRbac;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use InteractsWithRbac, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRbac();
    }

    public function test_administrator_creates_role(): void
    {
        $this->actingAs($this->admin())->post(route('admin.roles.store'), [
            'name' => 'Operações', 'slug' => 'operations', 'description' => 'Equipe de operações', 'active' => true,
            'permission_ids' => [],
        ])->assertRedirect(route('admin.roles.index'));

        $this->assertDatabaseHas('roles', ['slug' => 'operations']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'role_created']);
    }

    public function test_administrator_updates_role(): void
    {
        $role = Role::factory()->create();

        $this->actingAs($this->admin())->put(route('admin.roles.update', $role), [
            'name' => 'Operações Atualizadas', 'slug' => $role->slug, 'description' => null, 'active' => true,
            'permission_ids' => [],
        ])->assertRedirect(route('admin.roles.index'));

        $this->assertSame('Operações Atualizadas', $role->refresh()->name);
        $this->assertDatabaseHas('audit_logs', ['action' => 'role_updated', 'auditable_id' => $role->id]);
    }

    public function test_administrator_assigns_permissions_to_role(): void
    {
        $role = Role::factory()->create();
        $permission = Permission::where('slug', 'users.view')->firstOrFail();

        $this->actingAs($this->admin())->put(route('admin.roles.update', $role), [
            'name' => $role->name, 'slug' => $role->slug, 'description' => $role->description, 'active' => true,
            'permission_ids' => [$permission->id],
        ])->assertRedirect();

        $this->assertTrue($role->refresh()->permissions->contains($permission));
        $this->assertDatabaseHas('audit_logs', ['action' => 'permission_attached', 'auditable_id' => $role->id]);
    }

    public function test_user_without_permission_cannot_manage_roles(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.roles.index'))->assertForbidden();
    }

    public function test_delegated_manager_cannot_edit_a_role_with_administrative_permissions(): void
    {
        $manager = $this->roleManager();
        $role = Role::factory()->create();
        $role->permissions()->attach(Permission::where('slug', 'audit.view')->firstOrFail());

        $this->actingAs($manager)->put(route('admin.roles.update', $role), [
            'name' => 'Manipulada', 'slug' => 'manipulada', 'description' => null,
            'active' => false, 'permission_ids' => [],
        ])->assertForbidden();

        $role->refresh();
        $this->assertNotSame('Manipulada', $role->name);
        $this->assertTrue($role->active);
        $this->assertTrue($role->permissions()->where('slug', 'audit.view')->exists());
    }

    public function test_delegated_manager_cannot_remove_administrative_permission_through_direct_sync(): void
    {
        $manager = $this->roleManager();
        $role = Role::factory()->create();
        $administrative = Permission::where('slug', 'users.manage_roles')->firstOrFail();
        $role->permissions()->attach($administrative);
        $this->actingAs($manager);

        try {
            app(SyncRolePermissionsAction::class)->execute($role, []);
            $this->fail('A sincronização administrativa deveria ter sido bloqueada.');
        } catch (AuthorizationException) {
            $this->assertTrue($role->permissions()->whereKey($administrative->id)->exists());
        }
    }

    public function test_delegated_manager_cannot_add_administrative_permission_through_direct_sync(): void
    {
        $manager = $this->roleManager();
        $role = Role::factory()->create();
        $administrative = Permission::where('slug', 'roles.manage_permissions')->firstOrFail();
        $this->actingAs($manager);

        $this->expectException(AuthorizationException::class);
        app(SyncRolePermissionsAction::class)->execute($role, [$administrative->id]);
    }

    public function test_legitimate_administrator_can_update_an_administrative_role(): void
    {
        $administrator = $this->admin();
        $role = Role::factory()->create();
        $administrative = Permission::where('slug', 'audit.view')->firstOrFail();
        $role->permissions()->attach($administrative);

        $this->actingAs($administrator)->put(route('admin.roles.update', $role), [
            'name' => 'Administrativa Atualizada', 'slug' => $role->slug,
            'description' => null, 'active' => true, 'permission_ids' => [$administrative->id],
        ])->assertRedirect(route('admin.roles.index'));

        $this->assertSame('Administrativa Atualizada', $role->refresh()->name);
    }

    public function test_delegated_manager_can_update_a_common_role(): void
    {
        $manager = $this->roleManager();
        $role = Role::factory()->create();
        $operational = Permission::where('slug', 'companies.view')->firstOrFail();

        $this->actingAs($manager)->put(route('admin.roles.update', $role), [
            'name' => 'Operacional Atualizada', 'slug' => $role->slug,
            'description' => null, 'active' => true, 'permission_ids' => [$operational->id],
        ])->assertRedirect(route('admin.roles.index'));

        $this->assertSame('Operacional Atualizada', $role->refresh()->name);
        $this->assertTrue($role->permissions()->whereKey($operational->id)->exists());
    }

    public function test_administrator_can_remove_the_roles_last_permission_from_the_form(): void
    {
        $role = Role::factory()->create();
        $role->permissions()->attach(Permission::where('slug', 'users.view')->firstOrFail());

        $this->actingAs($this->admin())->put(route('admin.roles.update', $role), [
            'name' => $role->name,
            'slug' => $role->slug,
            'description' => $role->description,
            'active' => true,
            'permission_ids' => [''],
        ])->assertRedirect(route('admin.roles.index'));

        $this->assertDatabaseMissing('permission_role', ['role_id' => $role->id]);
    }

    public function test_explicit_empty_permission_array_leaves_role_without_permissions(): void
    {
        $role = Role::factory()->create();
        $role->permissions()->attach(Permission::where('slug', 'users.view')->firstOrFail());

        app(UpdateRoleAction::class)->execute($role, [
            'name' => $role->name,
            'slug' => $role->slug,
            'description' => $role->description,
            'active' => true,
            'permission_ids' => [],
        ]);

        $this->assertTrue($role->refresh()->permissions->isEmpty());
    }

    public function test_multiple_permissions_are_still_synchronized(): void
    {
        $role = Role::factory()->create();
        $role->permissions()->attach(Permission::where('slug', 'audit.view')->firstOrFail());
        $permissions = Permission::whereIn('slug', ['users.view', 'users.update'])->get();

        $this->actingAs($this->admin())->put(route('admin.roles.update', $role), [
            'name' => $role->name,
            'slug' => $role->slug,
            'description' => $role->description,
            'active' => true,
            'permission_ids' => $permissions->pluck('id')->all(),
        ])->assertRedirect(route('admin.roles.index'));

        $this->assertEqualsCanonicalizing($permissions->pluck('id')->all(), $role->refresh()->permissions->pluck('id')->all());
    }

    public function test_administrator_role_slug_cannot_be_changed_over_http(): void
    {
        $role = Role::where('slug', Role::ADMIN_SLUG)->firstOrFail();

        $this->actingAs($this->admin())->put(route('admin.roles.update', $role), [
            'name' => $role->name,
            'slug' => 'renamed_admin',
            'description' => $role->description,
            'active' => true,
            'permission_ids' => $role->permissions()->pluck('id')->all(),
        ])->assertSessionHasErrors('slug');

        $this->assertSame(Role::ADMIN_SLUG, $role->refresh()->slug);
    }

    public function test_administrator_role_cannot_be_deactivated_over_http(): void
    {
        $role = Role::where('slug', Role::ADMIN_SLUG)->firstOrFail();

        $this->actingAs($this->admin())->put(route('admin.roles.update', $role), [
            'name' => $role->name,
            'slug' => Role::ADMIN_SLUG,
            'description' => $role->description,
            'active' => false,
            'permission_ids' => $role->permissions()->pluck('id')->all(),
        ])->assertSessionHasErrors('active');

        $this->assertTrue($role->refresh()->active);
    }

    public function test_another_role_cannot_assume_the_reserved_admin_slug(): void
    {
        $role = Role::factory()->create();

        $this->actingAs($this->admin())->put(route('admin.roles.update', $role), [
            'name' => $role->name,
            'slug' => Role::ADMIN_SLUG,
            'description' => $role->description,
            'active' => true,
            'permission_ids' => [],
        ])->assertSessionHasErrors('slug');

        $this->assertNotSame(Role::ADMIN_SLUG, $role->refresh()->slug);
    }

    public function test_administrator_role_always_retains_every_permission(): void
    {
        $role = Role::where('slug', Role::ADMIN_SLUG)->firstOrFail();

        app(SyncRolePermissionsAction::class)->execute($role, []);

        $this->assertEqualsCanonicalizing(
            Permission::query()->pluck('id')->all(),
            $role->refresh()->permissions()->pluck('id')->all(),
        );
    }

    public function test_direct_action_rejects_mutating_the_administrator_identity(): void
    {
        $role = Role::where('slug', Role::ADMIN_SLUG)->firstOrFail();

        $this->expectException(ValidationException::class);

        app(UpdateRoleAction::class)->execute($role, [
            'slug' => 'manipulated',
            'active' => false,
        ]);
    }

    private function roleManager(): User
    {
        $role = Role::factory()->create();
        $role->permissions()->sync(Permission::whereIn('slug', [
            'roles.update', 'roles.manage_permissions',
        ])->pluck('id'));
        $manager = User::factory()->create();
        $manager->roles()->attach($role);

        return $manager;
    }
}
