<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\PendingCommand;
use Tests\Concerns\InteractsWithRbac;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use InteractsWithRbac, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRbac();
    }

    public function test_command_preserves_custom_permissions_of_another_role(): void
    {
        $role = Role::where('slug', 'sales_rep')->firstOrFail();
        $customPermission = Permission::where('slug', 'audit.view')->firstOrFail();
        $role->permissions()->sync([$customPermission->id]);

        $this->runCommand('admin-one@example.com')->assertSuccessful();

        $this->assertSame([$customPermission->id], $role->refresh()->permissions()->pluck('id')->all());
    }

    public function test_new_account_receives_administrator_role(): void
    {
        $this->runCommand('admin-two@example.com')->assertSuccessful();

        $user = User::where('email', 'admin-two@example.com')->firstOrFail();
        $this->assertTrue($user->roles->contains('slug', Role::ADMIN_SLUG));
    }

    public function test_command_ensures_administrator_has_all_permissions(): void
    {
        $admin = Role::where('slug', Role::ADMIN_SLUG)->firstOrFail();
        $admin->permissions()->detach(Permission::where('slug', 'audit.view')->firstOrFail());

        $this->runCommand('admin-three@example.com')->assertSuccessful();

        $this->assertEqualsCanonicalizing(
            Permission::query()->pluck('id')->all(),
            $admin->refresh()->permissions()->pluck('id')->all(),
        );
    }

    public function test_repeated_command_does_not_duplicate_relationships(): void
    {
        $this->runCommand('admin-four@example.com')->assertSuccessful();
        $this->runCommand('admin-five@example.com')->assertSuccessful();

        $admin = Role::where('slug', Role::ADMIN_SLUG)->firstOrFail();
        $this->assertSame(Permission::count(), $admin->permissions()->count());
        $this->assertSame(2, User::whereIn('email', ['admin-four@example.com', 'admin-five@example.com'])->count());
        $this->assertSame(2, $admin->users()->whereIn('email', ['admin-four@example.com', 'admin-five@example.com'])->count());
    }

    public function test_invalid_credentials_do_not_modify_rbac(): void
    {
        $rolePermissions = Role::with('permissions')->get()->mapWithKeys(
            fn (Role $role): array => [$role->slug => $role->permissions->pluck('id')->sort()->values()->all()],
        )->all();
        $roleCount = Role::count();
        $permissionCount = Permission::count();

        $this->artisan('crm:create-admin', ['--name' => 'Inválido', '--email' => 'not-an-email'])
            ->expectsQuestion('Senha', 'short')
            ->expectsQuestion('Confirme a senha', 'different')
            ->assertFailed();

        $this->assertSame($roleCount, Role::count());
        $this->assertSame($permissionCount, Permission::count());
        $this->assertSame($rolePermissions, Role::with('permissions')->get()->mapWithKeys(
            fn (Role $role): array => [$role->slug => $role->permissions->pluck('id')->sort()->values()->all()],
        )->all());
    }

    public function test_command_trims_and_lowercases_administrator_email(): void
    {
        $this->runCommand('  MixedAdmin@Example.COM  ')->assertSuccessful();

        $this->assertDatabaseHas('users', ['email' => 'mixedadmin@example.com']);
    }

    private function runCommand(string $email): PendingCommand
    {
        return $this->artisan('crm:create-admin', ['--name' => 'Administrador Teste', '--email' => $email])
            ->expectsQuestion('Senha', 'Strong-password-123')
            ->expectsQuestion('Confirme a senha', 'Strong-password-123');
    }
}
