<?php

namespace Tests\Feature\Imports;

use App\Actions\Imports\AnalyzeImportDedupAction;
use App\Models\Company;
use App\Models\Contact;
use App\Models\DataImport;
use App\Models\ImportRow;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\ImportDedupViewData;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\InteractsWithRbac;
use Tests\TestCase;

class ImportDedupTest extends TestCase
{
    use InteractsWithRbac, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
    }

    public function test_access_and_mutation_permissions_are_enforced(): void
    {
        $import = $this->mappedImport([['company' => ['legal_name' => 'Clínica Alfa']]]);
        $viewer = $this->userWithPermission('imports.view');
        $updater = $this->userWithPermission('imports.update');

        $this->actingAs($viewer)->get(route('imports.dedup.index', $import))->assertOk();
        $this->actingAs(User::factory()->create())->get(route('imports.dedup.index', $import))->assertForbidden();
        $this->actingAs($viewer)->post(route('imports.dedup.analyze', $import))->assertForbidden();
        $this->actingAs($updater)->post(route('imports.dedup.analyze', $import))->assertRedirect(route('imports.dedup.index', $import));
    }

    public function test_company_matches_respect_country_soft_deletes_and_possible_signals(): void
    {
        $exact = Company::factory()->create(['legal_name' => 'Fiscal Brasil', 'tax_id_country' => 'BR', 'tax_id' => '11222333000181']);
        $exact->delete();
        Company::factory()->create(['legal_name' => 'Fiscal Chile', 'tax_id_country' => 'CL', 'tax_id' => '11222333000181']);
        Company::factory()->create(['legal_name' => 'Nome Igual', 'trade_name' => 'Fantasia Igual', 'email' => 'empresa@example.test', 'phone' => '+5511999999999', 'website' => 'https://example.test']);
        Company::factory()->create(['legal_name' => 'Espaços   Internos', 'trade_name' => null, 'email' => null, 'phone' => null, 'website' => null]);
        $import = $this->mappedImport([
            ['company' => ['legal_name' => 'Fiscal Brasil', 'tax_id_country' => 'BR', 'tax_id' => '11222333000181']],
            ['company' => ['legal_name' => 'Nome Igual', 'trade_name' => 'Fantasia Igual', 'email' => 'empresa@example.test', 'phone' => '+5511999999999', 'website' => 'https://example.test']],
            ['company' => ['legal_name' => 'Sem Match']],
            ['company' => ['legal_name' => 'Espaços Internos']],
        ]);

        $this->analyze($import);
        [$fiscal, $possible, $clear, $whitespace] = $import->rows()->orderBy('row_number')->get()->all();
        $candidate = collect($fiscal->dedup_data['groups']['company']['candidates'])->firstWhere('id', $exact->id);
        $this->assertSame('exact', $candidate['strength']);
        $this->assertTrue($candidate['archived']);
        $this->assertCount(1, collect($fiscal->dedup_data['groups']['company']['candidates'])->where('strength', 'exact'));
        $this->assertSame('possible', $possible->dedup_data['groups']['company']['match']);
        $this->assertSame('clear', $clear->dedup_data['status']);
        $this->assertSame('possible', $whitespace->dedup_data['groups']['company']['match']);
        $this->assertSame(1, $import->refresh()->duplicate_rows);
    }

    public function test_contact_and_lead_matching_is_conservative_and_includes_archived_records(): void
    {
        $company = Company::factory()->create(['legal_name' => 'Contexto Ltda']);
        $contact = Contact::factory()->for($company)->create(['name' => 'João Silva', 'email' => 'joao@example.test', 'linkedin_url' => 'https://linkedin.com/in/joao']);
        $contact->delete();
        $lead = Lead::factory()->create(['name' => 'Maria', 'company_name' => 'Contexto Ltda', 'email' => 'maria@example.test']);
        $lead->delete();
        $import = $this->mappedImport([
            ['company' => ['legal_name' => 'Contexto Ltda'], 'contact' => ['name' => 'João Silva', 'email' => 'joao@example.test', 'linkedin_url' => 'https://linkedin.com/in/joao'], 'lead' => ['name' => 'Maria', 'company_name' => 'Contexto Ltda', 'email' => 'maria@example.test']],
            ['contact' => ['name' => 'João Silva']],
            ['lead' => ['name' => 'Maria']],
        ]);

        $this->analyze($import);
        [$matched, $contactNameOnly, $leadNameOnly] = $import->rows()->orderBy('row_number')->get()->all();
        $this->assertSame('exact', $matched->dedup_data['groups']['contact']['match']);
        $this->assertSame('exact', $matched->dedup_data['groups']['lead']['match']);
        $this->assertTrue($matched->dedup_data['groups']['contact']['candidates'][0]['archived']);
        $this->assertTrue($matched->dedup_data['groups']['lead']['candidates'][0]['archived']);
        $this->assertSame('none', $contactNameOnly->dedup_data['groups']['contact']['match']);
        $this->assertSame('none', $leadNameOnly->dedup_data['groups']['lead']['match']);
    }

    public function test_internal_duplicates_reference_the_first_row_and_reanalysis_replaces_results(): void
    {
        $data = ['company' => ['legal_name' => 'Duplicada', 'tax_id_country' => 'BR', 'tax_id' => '11222333000181'], 'contact' => ['name' => 'Contato', 'linkedin_url' => 'https://linkedin.com/in/duplicado'], 'lead' => ['email' => 'lead@example.test']];
        $import = $this->mappedImport([$data, $data]);

        $this->analyze($import);
        $first = $import->rows()->orderBy('row_number')->firstOrFail();
        $second = $import->rows()->orderBy('row_number')->get()[1];
        foreach (['company', 'contact', 'lead'] as $group) {
            $candidate = collect($second->dedup_data['groups'][$group]['candidates'])->firstWhere('source', 'import');
            $this->assertSame($first->id, $candidate['import_row_id']);
            $this->assertSame($first->row_number, $candidate['row_number']);
            $this->assertSame('exact', $candidate['strength']);
        }
        $this->analyze($import);
        $this->assertCount(1, collect($second->refresh()->dedup_data['groups']['company']['candidates'])->where('source', 'import'));
    }

    public function test_preview_errors_are_blocked_and_analysis_is_read_only_for_crm_and_source_data(): void
    {
        $import = $this->mappedImport([['company' => ['email' => 'invalid']]]);
        $row = $import->rows()->sole();
        $before = $this->businessSnapshot();

        $this->analyze($import);

        $row->refresh();
        $this->assertSame('blocked', $row->dedup_data['status']);
        $this->assertSame(['Empresa 2' => 'Registro 2'], $row->original_data);
        $this->assertSame(['company' => ['email' => 'invalid']], $row->normalized_data);
        $this->assertSame(ImportRow::STATUS_PARSED, $row->status);
        $this->assertNull($row->related_entity_type);
        $this->assertNull($row->related_entity_id);
        $this->assertSame($before, $this->businessSnapshot());
        $this->assertDatabaseHas('audit_logs', ['action' => 'import_dedup_analyzed', 'auditable_id' => $import->id]);
    }

    public function test_valid_decisions_reject_injected_candidates_and_impossible_fiscal_create_new(): void
    {
        $company = Company::factory()->create(['legal_name' => 'Fiscal', 'tax_id_country' => 'BR', 'tax_id' => '11222333000181']);
        $import = $this->mappedImport([['company' => ['legal_name' => 'Fiscal', 'tax_id_country' => 'BR', 'tax_id' => '11222333000181']]]);
        $this->analyze($import);
        $row = $import->rows()->sole();
        $user = $this->userWithPermission('imports.update');

        $this->actingAs($user)->put(route('imports.dedup.update', [$import, $row]), ['group' => 'company', 'action' => 'use_existing', 'candidate_ref' => $this->candidateRef('crm', 'company', $company->id + 999)])->assertSessionHasErrors('candidate_id');
        $this->actingAs($user)->put(route('imports.dedup.update', [$import, $row]), ['group' => 'company', 'action' => 'create_new'])->assertSessionHasErrors('action');
        $this->actingAs($user)->put(route('imports.dedup.update', [$import, $row]), ['group' => 'company', 'action' => 'use_existing', 'candidate_ref' => $this->candidateRef('crm', 'company', $company->id)])->assertSessionHasNoErrors();
        $this->assertSame('use_existing', $row->refresh()->dedup_data['groups']['company']['decision']['action']);
        $this->assertSame('resolved', $row->dedup_data['status']);
    }

    public function test_contact_and_lead_allow_create_new_override_and_import_candidate_is_scoped(): void
    {
        Contact::factory()->create(['email' => 'contact@example.test']);
        Lead::factory()->create(['email' => 'lead@example.test']);
        $import = $this->mappedImport([['contact' => ['name' => 'Contato', 'email' => 'contact@example.test'], 'lead' => ['email' => 'lead@example.test']]]);
        $this->analyze($import);
        $row = $import->rows()->sole();
        $user = $this->userWithPermission('imports.update');

        foreach (['contact', 'lead'] as $group) {
            $this->actingAs($user)->put(route('imports.dedup.update', [$import, $row]), ['group' => $group, 'action' => 'create_new'])->assertSessionHasNoErrors();
        }
        $this->assertSame('resolved', $row->refresh()->dedup_data['status']);

        $other = $this->mappedImport([['lead' => ['email' => 'other@example.test']]]);
        $this->actingAs($user)->put(route('imports.dedup.update', [$import, $row]), ['group' => 'lead', 'action' => 'reuse_import_row', 'candidate_ref' => $this->candidateRef('import', 'lead', $other->rows()->sole()->id)])->assertSessionHasErrors('candidate_id');
    }

    public function test_remapping_invalidates_dedup_and_counters(): void
    {
        $import = $this->mappedImport([['company' => ['legal_name' => 'Duplicada']], ['company' => ['legal_name' => 'Duplicada']]]);
        $this->analyze($import);
        $this->assertNotNull($import->rows()->first()->dedup_data);
        $row = $import->rows()->firstOrFail();
        $row->execution_data = ['version' => 1, 'status' => 'success'];
        $row->save();
        $metadata = $import->metadata;
        $metadata['execution'] = ['version' => 1];
        $metadata['execution_config'] = ['version' => 1];
        $import->update(['duplicate_rows' => 2, 'imported_rows' => 1, 'failed_rows' => 1, 'started_at' => now(), 'finished_at' => now(), 'metadata' => $metadata]);

        $user = $this->userWithPermission('imports.update');
        $this->actingAs($user)->put(route('imports.mapping.update', $import), ['columns' => [['source' => $import->metadata['header'][0], 'target' => 'company.legal_name']]])->assertSessionHasNoErrors();

        $this->assertNull($import->rows()->first()->refresh()->dedup_data);
        $this->assertNull($import->rows()->first()->execution_data);
        $this->assertArrayNotHasKey('dedup', $import->refresh()->metadata);
        $this->assertArrayNotHasKey('execution', $import->metadata);
        $this->assertArrayNotHasKey('execution_config', $import->metadata);
        $this->assertSame(0, $import->duplicate_rows);
        $this->assertSame(0, $import->imported_rows);
        $this->assertSame(0, $import->failed_rows);
        $this->assertNull($import->started_at);
        $this->assertNull($import->finished_at);
    }

    public function test_ui_escapes_data_and_hides_candidate_details_without_entity_permission(): void
    {
        Company::factory()->create(['legal_name' => '<script>alert(1)</script>']);
        $import = $this->mappedImport([['company' => ['legal_name' => '<script>alert(1)</script>']]]);
        $this->analyze($import);

        $response = $this->actingAs($this->userWithPermission('imports.view'))->get(route('imports.dedup.index', $import));
        $response->assertOk()->assertSee('não possui permissão para visualizar seus detalhes')->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_duplicate_screen_enriches_company_contact_and_lead_candidates_with_decision_references(): void
    {
        $company = Company::factory()->create(['legal_name' => 'Empresa Reimportada', 'tax_id_country' => 'BR', 'tax_id' => '11222333000181']);
        $contact = Contact::factory()->for($company)->create(['name' => 'Contato Reimportado', 'email' => 'contato-reimportado@example.test']);
        $lead = Lead::factory()->create(['name' => 'Lead Reimportado', 'company_name' => 'Empresa Reimportada', 'email' => 'lead-reimportado@example.test']);
        $import = $this->mappedImport([
            ['company' => ['legal_name' => $company->legal_name, 'tax_id_country' => 'BR', 'tax_id' => $company->tax_id]],
            ['contact' => ['name' => $contact->name, 'email' => $contact->email]],
            ['lead' => ['name' => $lead->name, 'company_name' => $lead->company_name, 'email' => $lead->email]],
        ]);
        $this->analyze($import);
        $user = $this->userWithImportViewAndUpdate();

        $view = app(ImportDedupViewData::class)->build($import, $user);
        foreach ($view['rows'] as $index => $row) {
            $group = ['company', 'contact', 'lead'][$index];
            $candidate = $row->dedup_data['groups'][$group]['candidates'][0];
            $this->assertSame('crm', $candidate['source']);
            $this->assertNotEmpty($candidate['decision_ref']);
        }

        $this->actingAs($user)->get(route('imports.dedup.index', $import))
            ->assertOk()
            ->assertSee('Usar registro correspondente');
    }

    public function test_exact_reimport_of_company_contact_and_lead_renders_and_accepts_reuse_create_and_skip(): void
    {
        $company = Company::factory()->create(['legal_name' => 'Clínica Reimportada', 'tax_id_country' => 'BR', 'tax_id' => '11222333000181']);
        $contact = Contact::factory()->for($company)->create(['name' => 'Ana Reimportada', 'email' => 'ana-reimportada@example.test']);
        $lead = Lead::factory()->create(['name' => 'Ana Reimportada', 'company_name' => 'Clínica Reimportada', 'email' => 'lead-ana-reimportada@example.test']);
        $import = $this->mappedImport([[
            'company' => ['legal_name' => $company->legal_name, 'tax_id_country' => $company->tax_id_country, 'tax_id' => $company->tax_id],
            'contact' => ['name' => $contact->name, 'email' => $contact->email],
            'lead' => ['name' => $lead->name, 'company_name' => $lead->company_name, 'email' => $lead->email],
        ]]);
        $user = $this->userWithImportViewAndUpdate();
        $this->actingAs($user)->post(route('imports.dedup.analyze', $import))
            ->assertRedirect(route('imports.dedup.index', $import));
        $row = $import->rows()->sole();
        $view = app(ImportDedupViewData::class)->build($import, $user);
        $presented = $view['rows']->firstOrFail()->dedup_data;

        foreach (['company', 'contact', 'lead'] as $group) {
            $this->assertNotEmpty($presented['groups'][$group]['candidates']);
            $this->assertNotEmpty($presented['groups'][$group]['candidates'][0]['decision_ref']);
        }
        $this->actingAs($user)->get(route('imports.dedup.index', $import))
            ->assertOk()
            ->assertSee('Clínica Reimportada')
            ->assertSee('Ana Reimportada')
            ->assertSee('Usar registro correspondente');

        $companyRef = $presented['groups']['company']['candidates'][0]['decision_ref'];
        $this->actingAs($user)->put(route('imports.dedup.update', [$import, $row]), ['group' => 'company', 'action' => 'use_existing', 'candidate_ref' => $companyRef])->assertSessionHasNoErrors();
        $this->actingAs($user)->put(route('imports.dedup.update', [$import, $row]), ['group' => 'contact', 'action' => 'create_new'])->assertSessionHasNoErrors();
        $this->actingAs($user)->put(route('imports.dedup.update', [$import, $row]), ['group' => 'lead', 'action' => 'skip'])->assertSessionHasNoErrors();

        $decisions = $row->refresh()->dedup_data['groups'];
        $this->assertSame('use_existing', $decisions['company']['decision']['action']);
        $this->assertSame('create_new', $decisions['contact']['decision']['action']);
        $this->assertSame('skip', $decisions['lead']['decision']['action']);
        $this->assertSame('resolved', $row->dedup_data['status']);
    }

    public function test_duplicate_screen_without_candidates_remains_available(): void
    {
        $import = $this->mappedImport([['company' => ['legal_name' => 'Empresa realmente inédita']]]);
        $this->analyze($import);

        $this->actingAs($this->userWithImportViewAndUpdate())->get(route('imports.dedup.index', $import))
            ->assertOk()
            ->assertSee('Nenhum candidato encontrado.')
            ->assertSee('Criar novo')
            ->assertSee('Ignorar');
    }

    public function test_candidates_are_limited_and_crm_queries_are_batched_per_chunk(): void
    {
        Company::factory()->count(7)->create(['legal_name' => 'Nome Compartilhado']);
        $rows = [];
        for ($index = 0; $index < 60; $index++) {
            $rows[] = [
                'company' => ['legal_name' => $index === 0 ? 'Nome Compartilhado' : 'Empresa '.$index],
                'contact' => ['name' => 'Contato '.$index, 'email' => "contato{$index}@example.test"],
                'lead' => ['email' => "lead{$index}@example.test"],
            ];
        }
        $import = $this->mappedImport($rows);
        $commercialQueries = 0;
        DB::listen(function ($query) use (&$commercialQueries): void {
            if (preg_match('/select .* from "(companies|contacts|leads)"/i', $query->sql) === 1) {
                $commercialQueries++;
            }
        });

        $this->analyze($import);

        $this->assertLessThanOrEqual(3, $commercialQueries);
        $this->assertCount(5, $import->rows()->orderBy('row_number')->firstOrFail()->dedup_data['groups']['company']['candidates']);
    }

    public function test_reuse_import_row_and_skip_are_persisted_without_links_or_crm_mutation(): void
    {
        $import = $this->mappedImport([['lead' => ['email' => 'same@example.test']], ['lead' => ['email' => 'same@example.test']]]);
        $this->analyze($import);
        [$first, $second] = $import->rows()->orderBy('row_number')->get()->all();
        $user = $this->userWithPermission('imports.update');
        $before = $this->businessSnapshot();

        $this->actingAs($user)->put(route('imports.dedup.update', [$import, $second]), ['group' => 'lead', 'action' => 'reuse_import_row', 'candidate_ref' => $this->candidateRef('import', 'lead', $first->id)])->assertSessionHasNoErrors();
        $this->assertSame('reuse_import_row', $second->refresh()->dedup_data['groups']['lead']['decision']['action']);
        $this->actingAs($user)->put(route('imports.dedup.update', [$import, $second]), ['group' => 'lead', 'action' => 'skip'])->assertSessionHasNoErrors();
        $this->assertSame('skip', $second->refresh()->dedup_data['groups']['lead']['decision']['action']);
        $this->assertNull($second->related_entity_type);
        $this->assertNull($second->related_entity_id);
        $this->assertSame($before, $this->businessSnapshot());
        $this->assertDatabaseHas('audit_logs', ['action' => 'import_dedup_decision_updated', 'auditable_id' => $second->id]);
    }

    private function analyze(DataImport $import): void
    {
        app(AnalyzeImportDedupAction::class)->execute($import, $this->userWithPermission('imports.update'));
    }

    private function candidateRef(string $source, string $entity, int $id): string
    {
        return Crypt::encryptString(json_encode(compact('source', 'entity', 'id'), JSON_THROW_ON_ERROR));
    }

    private function userWithImportViewAndUpdate(): User
    {
        $user = $this->userWithPermission('imports.update');
        $permissionIds = \App\Models\Permission::whereIn('slug', ['imports.view', 'companies.view', 'contacts.view', 'leads.view'])->pluck('id');
        $user->roles()->firstOrFail()->permissions()->attach($permissionIds);

        return $user;
    }

    /** @param list<array<string, mixed>> $rows */
    private function mappedImport(array $rows): DataImport
    {
        $targets = [];
        foreach ($rows as $data) {
            foreach ($data as $group => $fields) {
                foreach (array_keys($fields) as $field) {
                    $targets[] = $group.'.'.$field;
                }
            }
        }
        $targets = array_values(array_unique($targets));
        $mapping = [];
        $headers = [];
        foreach ($targets as $index => $target) {
            $header = 'Campo '.($index + 1);
            $headers[] = $header;
            $mapping[$header] = $target;
        }
        if ($headers === []) {
            $headers = ['Ignorada'];
        }
        $import = DataImport::factory()->create(['total_rows' => count($rows), 'metadata' => ['header' => $headers, 'mapping' => ['version' => 1, 'columns' => $mapping, 'ignored_columns' => $mapping === [] ? $headers : []]]]);
        foreach ($rows as $index => $data) {
            ImportRow::factory()->for($import, 'dataImport')->create(['row_number' => $index + 2, 'original_data' => ['Empresa 2' => 'Registro '.($index + 2)], 'normalized_data' => $data]);
        }

        return $import;
    }

    /** @return array<string, mixed> */
    private function businessSnapshot(): array
    {
        return ['companies' => Company::query()->withTrashed()->get()->toArray(), 'contacts' => Contact::query()->withTrashed()->get()->toArray(), 'leads' => Lead::query()->withTrashed()->get()->toArray(), 'opportunities' => Opportunity::query()->withTrashed()->get()->toArray()];
    }
}
