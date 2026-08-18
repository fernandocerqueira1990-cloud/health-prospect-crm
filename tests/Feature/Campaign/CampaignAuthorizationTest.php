<?php

namespace Tests\Feature\Campaign;

use App\Models\Campaign;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class CampaignAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
        ]);
    }

    public function test_campaign_policy_enforces_each_backend_permission(): void
    {
        $campaign = Campaign::factory()->create();
        $abilities = [
            'viewAny' => [Campaign::class, 'campaigns.view'],
            'view' => [$campaign, 'campaigns.view'],
            'create' => [Campaign::class, 'campaigns.create'],
            'update' => [$campaign, 'campaigns.update'],
            'delete' => [$campaign, 'campaigns.delete'],
        ];

        foreach ($abilities as $ability => [$subject, $permissionSlug]) {
            $unauthorized = User::factory()->create();
            $this->assertFalse(Gate::forUser($unauthorized)->allows($ability, $subject));

            $authorized = $this->userWithPermission($permissionSlug);
            $this->assertTrue(Gate::forUser($authorized)->allows($ability, $subject));
        }
    }

    public function test_admin_bypasses_campaign_policy_via_existing_global_rule(): void
    {
        $admin = $this->userWithRole('admin');
        $campaign = Campaign::factory()->create();

        foreach (['view', 'update', 'delete'] as $ability) {
            $this->assertTrue(Gate::forUser($admin)->allows($ability, $campaign));
        }

        foreach (['viewAny', 'create'] as $ability) {
            $this->assertTrue(Gate::forUser($admin)->allows($ability, Campaign::class));
        }
    }

    public function test_delete_permission_is_assigned_only_to_appropriate_default_roles(): void
    {
        $this->assertDatabaseHas('permissions', ['slug' => 'campaigns.delete']);

        foreach (['admin', 'marketing', 'tester'] as $roleSlug) {
            $this->assertTrue($this->roleHasPermission($roleSlug, 'campaigns.delete'));
        }

        foreach (['sales_manager', 'supervisor', 'sales_rep', 'analyst', 'readonly'] as $roleSlug) {
            $this->assertFalse($this->roleHasPermission($roleSlug, 'campaigns.delete'));
        }
    }

    public function test_readonly_role_can_only_view_campaigns(): void
    {
        $readonly = $this->userWithRole('readonly');
        $campaign = Campaign::factory()->create();

        $this->assertTrue(Gate::forUser($readonly)->allows('viewAny', Campaign::class));
        $this->assertTrue(Gate::forUser($readonly)->allows('view', $campaign));
        $this->assertFalse(Gate::forUser($readonly)->allows('create', Campaign::class));
        $this->assertFalse(Gate::forUser($readonly)->allows('update', $campaign));
        $this->assertFalse(Gate::forUser($readonly)->allows('delete', $campaign));
    }

    private function userWithPermission(string $slug): User
    {
        $role = Role::factory()->create();
        $role->permissions()->attach(Permission::where('slug', $slug)->firstOrFail());

        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }

    private function userWithRole(string $slug): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', $slug)->firstOrFail());

        return $user;
    }

    private function roleHasPermission(string $roleSlug, string $permissionSlug): bool
    {
        return Role::where('slug', $roleSlug)
            ->firstOrFail()
            ->permissions()
            ->where('slug', $permissionSlug)
            ->exists();
    }
}
