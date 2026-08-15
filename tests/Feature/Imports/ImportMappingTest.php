<?php

namespace Tests\Feature\Imports;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Contact;
use App\Models\DataImport;
use App\Models\ImportRow;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\InteractsWithRbac;
use Tests\TestCase;

class ImportMappingTest extends TestCase
{
    use InteractsWithRbac, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
    }

    public function test_authorized_user_can_open_mapper_with_suggestions_three_samples_and_escaped_content(): void
    {
        $dataImport = $this->import(['Nome da Empresa', 'Nome'], [
            ['Nome da Empresa' => '<script>alert(1)</script>', 'Nome' => 'A'],
            ['Nome da Empresa' => 'B', 'Nome' => 'B'], ['Nome da Empresa' => 'C', 'Nome' => 'C'],
            ['Nome da Empresa' => 'D', 'Nome' => 'D'],
        ]);

        $response = $this->actingAs($this->userWithPermission('imports.update'))->get(route('imports.mapping.edit', $dataImport));

        $response->assertOk()->assertSee('Mapear colunas')->assertSee('company.trade_name')->assertDontSee('company.assigned_user_id');
        $response->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $response->assertDontSee('>D<', false);
        $response->assertSee('<input type="hidden" name="columns[1][source]" value="Nome">', false);
        $response->assertDontSee('<option value="contact.name" selected>', false);
    }

    public function test_valid_mapping_normalizes_rows_preserves_original_metadata_and_creates_no_entities(): void
    {
        $original = ['Nome da Empresa' => '  Clínica   ABC ', 'Email' => ' COMERCIAL@EMPRESA.COM ', 'Ignorada' => 'segredo'];
        $dataImport = $this->import(array_keys($original), [$original], ['delimiter' => ',']);
        $counts = $this->entityCounts();

        $this->actingAs($user = $this->userWithPermission('imports.update'))
            ->put(route('imports.mapping.update', $dataImport), ['columns' => [
                ['source' => 'Nome da Empresa', 'target' => 'company.trade_name'],
                ['source' => 'Email', 'target' => 'company.email'],
                ['source' => 'Ignorada', 'target' => ''],
            ]])->assertRedirect(route('imports.mapping.edit', $dataImport));

        $row = $dataImport->rows()->sole();
        $dataImport->refresh();
        $this->assertEqualsCanonicalizing($original, $row->original_data);
        $this->assertEqualsCanonicalizing(['company' => ['trade_name' => 'Clínica ABC', 'email' => 'comercial@empresa.com']], $row->normalized_data);
        $this->assertSame(',', $dataImport->metadata['delimiter']);
        $this->assertEqualsCanonicalizing(['Nome da Empresa' => 'company.trade_name', 'Email' => 'company.email'], $dataImport->metadata['mapping']['columns']);
        $this->assertSame(['Ignorada'], $dataImport->metadata['mapping']['ignored_columns']);
        $this->assertSame(1, $dataImport->metadata['mapping']['version']);
        $this->assertSame($user->id, $dataImport->metadata['mapping']['mapped_by_user_id']);
        $this->assertNotEmpty($dataImport->metadata['mapping']['mapped_at']);
        $this->assertSame($counts, $this->entityCounts());
        $audit = AuditLog::query()->where('action', 'import_mapping_updated')->sole();
        $this->assertEqualsCanonicalizing(['Nome da Empresa' => 'company.trade_name', 'Email' => 'company.email'], $audit->after['columns']);
        $this->assertStringNotContainsString('segredo', json_encode($audit->after, JSON_THROW_ON_ERROR));
    }

    public function test_remapping_rebuilds_normalized_data_and_removes_old_targets(): void
    {
        $dataImport = $this->import(['Empresa', 'Contato'], [['Empresa' => 'Clínica', 'Contato' => 'Ana']]);
        $user = $this->userWithPermission('imports.update');
        $this->actingAs($user)->put(route('imports.mapping.update', $dataImport), ['columns' => [
            ['source' => 'Empresa', 'target' => 'company.trade_name'], ['source' => 'Contato', 'target' => 'contact.name'],
        ]]);

        $this->actingAs($user)->put(route('imports.mapping.update', $dataImport), ['columns' => [
            ['source' => 'Empresa', 'target' => 'lead.company_name'], ['source' => 'Contato', 'target' => ''],
        ]])->assertSessionHasNoErrors();

        $this->assertSame(['lead' => ['company_name' => 'Clínica']], $dataImport->rows()->sole()->normalized_data);
        $this->assertSame(['Empresa' => 'lead.company_name'], $dataImport->refresh()->metadata['mapping']['columns']);
        $this->assertDatabaseCount('audit_logs', 2);
    }

    public function test_invalid_target_duplicate_target_unknown_source_and_missing_source_are_rejected_atomically(): void
    {
        $dataImport = $this->import(['Empresa', 'Cidade'], [['Empresa' => 'A', 'Cidade' => 'B']]);
        $user = $this->userWithPermission('imports.update');

        $payloads = [
            [['source' => 'Empresa', 'target' => 'company.assigned_user_id'], ['source' => 'Cidade', 'target' => 'company.city']],
            [['source' => 'Empresa', 'target' => 'company.trade_name'], ['source' => 'Cidade', 'target' => 'company.trade_name']],
            [['source' => 'Inexistente', 'target' => 'company.trade_name'], ['source' => 'Cidade', 'target' => 'company.city']],
            [['source' => 'Empresa', 'target' => 'company.trade_name']],
        ];

        foreach ($payloads as $columns) {
            $this->actingAs($user)->put(route('imports.mapping.update', $dataImport), ['columns' => $columns])->assertSessionHasErrors();
            $this->assertNull($dataImport->rows()->sole()->normalized_data);
            $this->assertArrayNotHasKey('mapping', $dataImport->refresh()->metadata);
        }
    }

    public function test_failed_import_and_users_without_update_permission_cannot_map(): void
    {
        $parsed = $this->import(['Empresa'], [['Empresa' => 'A']]);
        $failed = $this->import(['Empresa'], [], status: DataImport::STATUS_FAILED);
        $withoutPermission = User::factory()->create();

        $this->actingAs($withoutPermission)->get(route('imports.mapping.edit', $parsed))->assertForbidden();
        $this->actingAs($withoutPermission)->put(route('imports.mapping.update', $parsed), ['columns' => [['source' => 'Empresa', 'target' => 'company.trade_name']]])->assertForbidden();
        $this->actingAs($this->userWithPermission('imports.update'))->get(route('imports.mapping.edit', $failed))->assertStatus(409);
        $this->actingAs($this->userWithPermission('imports.update'))->put(route('imports.mapping.update', $failed), ['columns' => [['source' => 'Empresa', 'target' => 'company.trade_name']]])->assertSessionHasErrors('mapping');
    }

    public function test_show_link_is_visible_only_for_authorized_user_and_parsed_import(): void
    {
        $parsed = $this->import(['Empresa'], []);
        $failed = $this->import(['Empresa'], [], status: DataImport::STATUS_FAILED);
        $user = $this->userWithPermission('imports.update');
        $user->roles()->firstOrFail()->permissions()->attach(Permission::where('slug', 'imports.view')->firstOrFail());

        $this->actingAs($user)->get(route('imports.show', $parsed))->assertSee('Mapear colunas');
        $this->actingAs($user)->get(route('imports.show', $failed))->assertDontSee('Mapear colunas');
        $this->actingAs($this->userWithPermission('imports.view'))->get(route('imports.show', $parsed))->assertDontSee('Mapear colunas');
    }

    public function test_mapping_large_import_uses_chunks_and_normalizes_every_row(): void
    {
        $dataImport = $this->import(['Empresa'], []);
        $timestamp = now();
        $rows = [];
        for ($index = 1; $index <= 1001; $index++) {
            $rows[] = ['import_id' => $dataImport->id, 'row_number' => $index + 1, 'status' => ImportRow::STATUS_PARSED, 'original_data' => json_encode(['Empresa' => " Empresa {$index} "], JSON_THROW_ON_ERROR), 'created_at' => $timestamp, 'updated_at' => $timestamp];
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('import_rows')->insert($chunk);
        }

        $this->actingAs($this->userWithPermission('imports.update'))->put(route('imports.mapping.update', $dataImport), ['columns' => [['source' => 'Empresa', 'target' => 'company.trade_name']]])->assertSessionHasNoErrors();

        $this->assertSame(1001, $dataImport->rows()->whereNotNull('normalized_data')->count());
        $this->assertSame(['company' => ['trade_name' => 'Empresa 1001']], $dataImport->rows()->orderByDesc('id')->firstOrFail()->normalized_data);
    }

    public function test_permission_seeders_assign_imports_update_by_least_privilege(): void
    {
        $this->assertDatabaseHas('permissions', ['slug' => 'imports.update']);
        foreach (['admin', 'sales_manager', 'supervisor', 'marketing'] as $role) {
            $this->assertTrue(Role::where('slug', $role)->firstOrFail()->permissions()->where('slug', 'imports.update')->exists());
        }
        foreach (['sales_rep', 'analyst', 'readonly'] as $role) {
            $this->assertFalse(Role::where('slug', $role)->firstOrFail()->permissions()->where('slug', 'imports.update')->exists());
        }
    }

    /** @param list<string> $headers @param list<array<string, mixed>> $rows @param array<string, mixed> $extraMetadata */
    private function import(array $headers, array $rows, array $extraMetadata = [], string $status = DataImport::STATUS_PARSED): DataImport
    {
        $dataImport = DataImport::factory()->create(['status' => $status, 'metadata' => array_merge($extraMetadata, ['header' => $headers]), 'total_rows' => count($rows)]);
        foreach ($rows as $index => $original) {
            ImportRow::factory()->for($dataImport, 'dataImport')->create(['row_number' => $index + 2, 'original_data' => $original, 'normalized_data' => null]);
        }

        return $dataImport;
    }

    /** @return array{companies: int, contacts: int, leads: int, opportunities: int} */
    private function entityCounts(): array
    {
        return ['companies' => Company::query()->count(), 'contacts' => Contact::query()->count(), 'leads' => Lead::query()->count(), 'opportunities' => Opportunity::query()->count()];
    }
}
