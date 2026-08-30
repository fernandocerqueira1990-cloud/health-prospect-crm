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
use App\Services\ImportPreviewValidator;
use App\Support\ImportFieldCatalog;
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
        $response->assertSee('A · B · C', false)->assertDontSee('A · B · C · D', false);
        $response->assertSee('<input type="hidden" name="columns[1][source]" value="Nome">', false);
        $response->assertDontSee('<option value="contact.name" selected>', false);
    }

    public function test_official_template_is_automatically_mapped_and_unknown_column_stays_ignored(): void
    {
        $headers = ['empresa_nome_fantasia', 'empresa_cnpj', 'contato_nome', 'contato_email', 'lead_nome', 'lead_status', 'campo_desconhecido'];
        $dataImport = $this->import($headers, []);

        $response = $this->actingAs($this->userWithPermission('imports.update'))->get(route('imports.mapping.edit', $dataImport));

        $response->assertOk();
        foreach (['company.trade_name', 'company.tax_id', 'contact.name', 'contact.email', 'lead.name', 'lead.status'] as $target) {
            $response->assertSee('<option value="'.$target.'" selected>', false);
        }
        $response->assertSee('<input type="hidden" name="columns[6][source]" value="campo_desconhecido">', false);
        $response->assertSee('<option value="">Ignorar</option>', false);
    }

    public function test_generic_unknown_file_has_no_automatic_mapping(): void
    {
        $dataImport = $this->import(['Coluna XPTO', 'Outro dado externo'], []);

        $response = $this->actingAs($this->userWithPermission('imports.update'))->get(route('imports.mapping.edit', $dataImport));

        $response->assertOk()->assertDontSee(' selected>', false);
    }

    public function test_saved_manual_mapping_overrides_official_template_suggestion(): void
    {
        $dataImport = $this->import(['empresa_nome_fantasia'], []);
        $user = $this->userWithPermission('imports.update');

        $this->actingAs($user)->put(route('imports.mapping.update', $dataImport), ['columns' => [
            ['source' => 'empresa_nome_fantasia', 'target' => 'lead.company_name'],
        ]])->assertSessionHasNoErrors();

        $response = $this->actingAs($user)->get(route('imports.mapping.edit', $dataImport));

        $response->assertOk()
            ->assertSee('<option value="lead.company_name" selected>', false)
            ->assertDontSee('<option value="company.trade_name" selected>', false);
        $this->assertSame(['empresa_nome_fantasia' => 'lead.company_name'], $dataImport->refresh()->metadata['mapping']['columns']);
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

    public function test_complete_official_template_example_reaches_preview_without_friendly_value_errors(): void
    {
        $original = [
            'empresa_nome_fantasia' => 'Clínica Exemplo', 'empresa_razao_social' => 'Clínica Exemplo Ltda.',
            'empresa_cnpj' => '04.252.011/0001-10', 'empresa_pais_id_fiscal' => ' Brasil ',
            'empresa_segmento' => 'Saúde', 'empresa_categoria' => 'Clínica', 'empresa_site' => 'clinica.example.com.br',
            'empresa_telefone' => '(11) 3333-4444', 'empresa_email' => 'contato@clinica.example',
            'empresa_logradouro' => 'Rua Saúde', 'empresa_numero' => '100', 'empresa_complemento' => 'Sala 1',
            'empresa_bairro' => 'Centro', 'empresa_cidade' => 'São Paulo', 'empresa_estado_uf' => 'SP',
            'empresa_cep' => '01001000', 'empresa_estimativa_funcionarios' => '25', 'empresa_prioridade' => ' A ',
            'empresa_observacoes' => 'Exemplo oficial', 'contato_nome' => 'Ana Silva', 'contato_cargo' => 'Diretora',
            'contato_departamento' => 'Diretoria', 'contato_email' => 'ana@clinica.example',
            'contato_telefone' => '(11) 99999-1111', 'contato_whatsapp' => '(11) 99999-1111',
            'contato_linkedin' => 'https://www.linkedin.com/in/ana-silva', 'contato_papel_decisao' => ' Decisor ',
            'contato_nivel_influencia' => ' ALTO ', 'contato_observacoes' => 'Contato principal',
            'lead_nome' => 'Ana Silva', 'lead_empresa' => 'Clínica Exemplo', 'lead_cargo' => 'Diretora',
            'lead_email' => 'ana@clinica.example', 'lead_telefone' => '(11) 99999-1111',
            'lead_whatsapp' => '(11) 99999-1111', 'lead_status' => ' NOVO ', 'lead_prioridade' => 'A',
            'lead_temperatura' => ' Morno ', 'lead_score' => '80', 'lead_observacoes' => 'Lead de exemplo',
        ];
        $catalog = app(ImportFieldCatalog::class);
        $mapping = collect(array_keys($original))->mapWithKeys(fn (string $header): array => [$header => $catalog->suggest($header)])->all();
        $this->assertNotContains(null, $mapping, true);
        $dataImport = $this->import(array_keys($original), [$original]);

        $this->actingAs($this->userWithPermission('imports.update'))
            ->put(route('imports.mapping.update', $dataImport), ['columns' => collect($mapping)->map(fn (string $target, string $source): array => compact('source', 'target'))->values()->all()])
            ->assertSessionHasNoErrors();

        $row = $dataImport->rows()->sole();
        $validation = app(ImportPreviewValidator::class)->validate($row->normalized_data, array_values($mapping));
        $friendlyFields = ['company.tax_id_country', 'company.priority', 'contact.decision_role', 'contact.influence_level', 'lead.status', 'lead.priority'];
        $friendlyIssues = array_filter($validation['issues'], fn (array $issue): bool => in_array($issue['field'], $friendlyFields, true));

        $this->assertSame('BR', $row->normalized_data['company']['tax_id_country']);
        $this->assertSame('high', $row->normalized_data['company']['priority']);
        $this->assertSame('decision_maker', $row->normalized_data['contact']['decision_role']);
        $this->assertSame('high', $row->normalized_data['contact']['influence_level']);
        $this->assertSame('new', $row->normalized_data['lead']['status']);
        $this->assertSame('high', $row->normalized_data['lead']['priority']);
        $this->assertSame('warm', $row->normalized_data['lead']['temperature']);
        $this->assertSame([], array_values($friendlyIssues));
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
