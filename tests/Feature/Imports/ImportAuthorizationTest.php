<?php

namespace Tests\Feature\Imports;

use App\Models\DataImport;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithRbac;
use Tests\TestCase;

class ImportAuthorizationTest extends TestCase
{
    use InteractsWithRbac, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
        Storage::fake('imports');
    }

    public function test_authorized_user_can_view_imports(): void
    {
        $this->actingAs($this->userWithPermission('imports.view'))->get(route('imports.index'))->assertOk()->assertSee('Importações');
    }

    public function test_unauthorized_user_cannot_view_imports(): void
    {
        $this->actingAs(User::factory()->create())->get(route('imports.index'))->assertForbidden();
    }

    public function test_authorized_user_can_import(): void
    {
        $response = $this->actingAs($this->userWithPermission('imports.create'))->post(route('imports.store'), ['file' => UploadedFile::fake()->createWithContent('dados.csv', "Nome,Email\nAna,ana@example.com\n")]);

        $response->assertRedirect();
        $this->assertDatabaseHas('imports', ['status' => DataImport::STATUS_PARSED, 'total_rows' => 1]);
    }

    public function test_user_without_create_permission_cannot_import(): void
    {
        $this->actingAs($this->userWithPermission('imports.view'))->post(route('imports.store'), ['file' => UploadedFile::fake()->createWithContent('dados.csv', "Nome,Email\nAna,ana@example.com\n")])->assertForbidden();
        $this->assertDatabaseCount('imports', 0);
    }

    public function test_user_without_delete_permission_cannot_delete_import(): void
    {
        $dataImport = DataImport::factory()->create();

        $this->actingAs($this->userWithPermission('imports.view'))
            ->delete(route('imports.destroy', $dataImport))
            ->assertForbidden();

        $this->assertDatabaseHas('imports', ['id' => $dataImport->id]);
    }
}
