<?php

namespace Tests\Feature\Imports;

use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyImportPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_seeder_removes_only_legacy_imports_execute_permission_and_its_assignments(): void
    {
        $legacy = Permission::factory()->create(['slug' => 'imports.execute']);
        $unrelated = Permission::factory()->create(['slug' => 'custom.permission']);
        $role = Role::factory()->create();
        $role->permissions()->attach([$legacy->id, $unrelated->id]);

        $this->seed(PermissionSeeder::class);

        $this->assertDatabaseMissing('permissions', ['slug' => 'imports.execute']);
        $this->assertDatabaseMissing('permission_role', ['permission_id' => $legacy->id]);
        $this->assertDatabaseHas('permissions', ['slug' => 'custom.permission']);
        $this->assertDatabaseHas('permission_role', ['permission_id' => $unrelated->id, 'role_id' => $role->id]);
    }
}
