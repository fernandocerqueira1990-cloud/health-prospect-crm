<?php

namespace Tests\Feature\Admin;

use App\Actions\Users\AssignUserRolesAction;
use App\Actions\Users\UpdateUserAction;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\InteractsWithRbac;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use InteractsWithRbac, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRbac();
    }

    public function test_administrator_lists_users(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->admin())->get(route('admin.users.index'))->assertOk()->assertSee($target->email);
    }

    public function test_administrator_creates_user_with_role(): void
    {
        $role = Role::where('slug', 'sales_rep')->firstOrFail();
        $response = $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'name' => 'Nova Pessoa', 'email' => 'nova@example.com', 'password' => 'Strong-password-123',
            'password_confirmation' => 'Strong-password-123', 'active' => true, 'role_ids' => [$role->id],
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $user = User::where('email', 'nova@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('Strong-password-123', $user->password));
        $this->assertTrue($user->roles->contains($role));
        $this->assertDatabaseHas('audit_logs', ['action' => 'user_created', 'auditable_id' => $user->id]);
    }

    public function test_administrator_updates_user(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->admin())->put(route('admin.users.update', $target), [
            'name' => 'Nome Atualizado', 'email' => $target->email, 'password' => '', 'password_confirmation' => '',
            'active' => true, 'role_ids' => [],
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', ['id' => $target->id, 'name' => 'Nome Atualizado']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user_updated', 'auditable_id' => $target->id]);
    }

    public function test_administrator_deactivates_user(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->admin())->put(route('admin.users.update', $target), [
            'name' => $target->name, 'email' => $target->email, 'active' => false, 'role_ids' => [],
        ])->assertRedirect();

        $this->assertFalse($target->refresh()->active);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user_deactivated', 'auditable_id' => $target->id]);
    }

    public function test_common_user_cannot_manage_users(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.users.store'), [])->assertForbidden();
    }

    public function test_administrator_can_remove_the_users_last_role_from_the_form(): void
    {
        $target = User::factory()->create();
        $target->roles()->attach(Role::where('slug', 'sales_rep')->firstOrFail());

        $this->actingAs($this->admin())->put(route('admin.users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'active' => true,
            'role_ids' => [''],
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('role_user', ['user_id' => $target->id]);
    }

    public function test_explicit_empty_role_array_leaves_user_without_roles(): void
    {
        $target = User::factory()->create();
        $target->roles()->attach(Role::where('slug', 'sales_rep')->firstOrFail());

        app(UpdateUserAction::class)->execute($target, [
            'name' => $target->name,
            'email' => $target->email,
            'active' => true,
            'role_ids' => [],
        ]);

        $this->assertTrue($target->refresh()->roles->isEmpty());
    }

    public function test_multiple_roles_are_still_synchronized(): void
    {
        $target = User::factory()->create();
        $target->roles()->attach(Role::where('slug', 'readonly')->firstOrFail());
        $roles = Role::whereIn('slug', ['sales_rep', 'marketing'])->get();

        $this->actingAs($this->admin())->put(route('admin.users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'active' => true,
            'role_ids' => $roles->pluck('id')->all(),
        ])->assertRedirect(route('admin.users.index'));

        $this->assertEqualsCanonicalizing($roles->pluck('id')->all(), $target->refresh()->roles->pluck('id')->all());
    }

    public function test_last_active_administrator_cannot_be_deactivated(): void
    {
        $administrator = $this->admin();

        $this->expectException(ValidationException::class);

        app(UpdateUserAction::class)->execute($administrator, [
            'name' => $administrator->name,
            'email' => $administrator->email,
            'active' => false,
        ]);
    }

    public function test_last_active_administrator_cannot_lose_admin_role(): void
    {
        $administrator = $this->admin();

        $this->expectException(ValidationException::class);

        app(AssignUserRolesAction::class)->execute($administrator, []);
    }

    public function test_last_active_administrator_cannot_be_deactivated_and_demoted_together(): void
    {
        $administrator = $this->admin();

        $this->expectException(ValidationException::class);

        app(UpdateUserAction::class)->execute($administrator, [
            'name' => $administrator->name,
            'email' => $administrator->email,
            'active' => false,
            'role_ids' => [],
        ]);
    }

    public function test_administrator_can_be_deactivated_when_another_active_administrator_exists(): void
    {
        $target = $this->admin();
        $actor = $this->admin();
        $adminRole = Role::where('slug', Role::ADMIN_SLUG)->firstOrFail();

        $this->actingAs($actor)->put(route('admin.users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'active' => false,
            'role_ids' => [$adminRole->id],
        ])->assertRedirect(route('admin.users.index'));

        $this->assertFalse($target->refresh()->active);
    }

    public function test_admin_role_can_be_removed_when_another_active_administrator_exists(): void
    {
        $target = $this->admin();
        $actor = $this->admin();

        $this->actingAs($actor)->put(route('admin.users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'active' => true,
            'role_ids' => [''],
        ])->assertRedirect(route('admin.users.index'));

        $this->assertFalse($target->refresh()->roles->contains('slug', Role::ADMIN_SLUG));
    }

    public function test_common_user_is_not_affected_by_last_administrator_guard(): void
    {
        $this->admin();
        $user = User::factory()->create();

        app(UpdateUserAction::class)->execute($user, [
            'name' => $user->name,
            'email' => $user->email,
            'active' => false,
            'role_ids' => [],
        ]);

        $this->assertFalse($user->refresh()->active);
    }

    public function test_manipulated_http_request_cannot_remove_last_active_administrator(): void
    {
        $administrator = $this->admin();

        $this->actingAs($administrator)->put(route('admin.users.update', $administrator), [
            'name' => $administrator->name,
            'email' => $administrator->email,
            'active' => false,
            'role_ids' => [''],
        ])->assertSessionHasErrors(['active', 'role_ids']);

        $administrator->refresh();
        $this->assertTrue($administrator->active);
        $this->assertTrue($administrator->roles->contains('slug', Role::ADMIN_SLUG));
    }

    public function test_created_user_email_is_trimmed_and_lowercased(): void
    {
        $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'name' => 'E-mail Canônico',
            'email' => '  User@Example.COM  ',
            'password' => 'Strong-password-123',
            'password_confirmation' => 'Strong-password-123',
            'active' => true,
            'role_ids' => [],
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', ['email' => 'user@example.com']);
    }

    public function test_updated_user_email_is_trimmed_and_lowercased(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->admin())->put(route('admin.users.update', $target), [
            'name' => $target->name,
            'email' => '  MixedCase@Example.COM ',
            'active' => true,
            'role_ids' => [],
        ])->assertRedirect(route('admin.users.index'));

        $this->assertSame('mixedcase@example.com', $target->refresh()->email);
    }

    public function test_case_variation_cannot_create_a_logical_duplicate_email(): void
    {
        User::factory()->create(['email' => 'user@example.com']);

        $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'name' => 'Duplicado',
            'email' => ' USER@EXAMPLE.COM ',
            'password' => 'Strong-password-123',
            'password_confirmation' => 'Strong-password-123',
            'active' => true,
            'role_ids' => [],
        ])->assertSessionHasErrors('email');

        $this->assertSame(1, User::where('email', 'user@example.com')->count());
    }
}
