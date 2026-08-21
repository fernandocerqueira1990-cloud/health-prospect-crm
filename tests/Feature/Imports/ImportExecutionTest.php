<?php

namespace Tests\Feature\Imports;

use App\Actions\Contacts\CreateContactAction;
use App\Actions\Imports\AnalyzeImportDedupAction;
use App\Actions\Imports\ExecuteImportAction;
use App\Actions\Imports\UpdateImportDedupDecisionAction;
use App\Exceptions\ImportExecutionException;
use App\Models\AuditLog;
use App\Models\Channel;
use App\Models\Company;
use App\Models\Contact;
use App\Models\DataImport;
use App\Models\ImportRow;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\LeadSourceEvent;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\ImportIntegrityService;
use Database\Seeders\ChannelSeeder;
use Database\Seeders\LeadSourceSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Concerns\InteractsWithRbac;
use Tests\TestCase;

class ImportExecutionTest extends TestCase
{
    use InteractsWithRbac, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class, LeadSourceSeeder::class, ChannelSeeder::class]);
    }

    public function test_lead_creation_uses_backend_source_and_selected_active_channel(): void
    {
        $channel = Channel::query()->where('slug', 'linkedin')->firstOrFail();
        $import = $this->executableImport([['lead' => ['name' => 'Lead Importado', 'email' => 'lead@example.test']]]);

        $this->execute($import, $channel->id);

        $lead = Lead::query()->sole();
        $this->assertSame('importacao', $lead->source->slug);
        $this->assertSame($channel->id, $lead->channel_id);
        $event = LeadSourceEvent::query()->sole();
        $this->assertSame('importacao', $event->source);
        $this->assertSame('linkedin', $event->channel);
        $config = $import->refresh()->metadata['execution_config'];
        $this->assertSame(1, $config['version']);
        $this->assertSame('importacao', $config['lead_source_slug']);
        $this->assertSame($channel->id, $config['lead_channel_id']);
    }

    public function test_lead_source_is_resolved_by_slug_and_must_be_active(): void
    {
        $channel = Channel::query()->where('active', true)->firstOrFail();
        $source = LeadSource::query()->where('slug', 'importacao')->firstOrFail();
        $source->delete();
        $import = $this->executableImport([['lead' => ['name' => 'Lead']]]);

        $this->expectExceptionObject(new ImportExecutionException('import_source_unavailable', 'A origem obrigatória de importação não está disponível.'));
        $this->execute($import, $channel->id);
    }

    public function test_inactive_import_source_blocks_execution(): void
    {
        LeadSource::query()->where('slug', 'importacao')->update(['active' => false]);
        $import = $this->executableImport([['lead' => ['name' => 'Lead']]]);

        $this->expectException(ImportExecutionException::class);
        $this->execute($import, Channel::query()->where('active', true)->value('id'));
    }

    public function test_channel_is_required_and_request_rejects_inactive_or_internal_ids(): void
    {
        $import = $this->executableImport([['lead' => ['name' => 'Lead']]]);
        $user = $this->userWithPermission('imports.update');
        $inactive = Channel::query()->firstOrFail();
        $inactive->update(['active' => false]);

        $this->actingAs($user)->post(route('imports.execute', $import), [])->assertSessionHasErrors('lead_channel_id');
        $this->actingAs($user)->post(route('imports.execute', $import), ['lead_channel_id' => $inactive->id])->assertSessionHasErrors('lead_channel_id');
        $this->actingAs($user)->post(route('imports.execute', $import), ['lead_channel_id' => 999999])->assertSessionHasErrors('lead_channel_id');
        $this->actingAs($user)->post(route('imports.execute', $import), ['lead_channel_id' => Channel::query()->where('active', true)->value('id'), 'source_id' => 1, 'channel_id' => 1, 'assigned_user_id' => 1])->assertSessionHasErrors(['source_id', 'channel_id', 'assigned_user_id']);
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_company_contact_only_and_existing_lead_do_not_require_channel(): void
    {
        $existing = Lead::factory()->create(['name' => 'Existente']);
        $import = $this->executableImport([
            ['company' => ['legal_name' => 'Empresa Nova'], 'contact' => ['name' => 'Contato Novo']],
            ['lead' => ['name' => 'Não sobrescrever']],
        ], [
            [],
            ['lead' => $this->decision('use_existing', [['source' => 'crm', 'entity' => 'lead', 'id' => $existing->id]])],
        ]);

        $this->execute($import);

        $this->assertDatabaseHas('companies', ['legal_name' => 'Empresa Nova']);
        $this->assertDatabaseHas('contacts', ['name' => 'Contato Novo']);
        $this->assertSame('Existente', $existing->refresh()->name);
        $this->assertNull($import->refresh()->metadata['execution_config']['lead_channel_id']);
    }

    public function test_company_create_use_reuse_and_skip_are_executed_in_row_order(): void
    {
        $existing = Company::factory()->create(['legal_name' => 'Existente']);
        $import = $this->executableImport([
            ['company' => ['legal_name' => 'Nova']],
            ['company' => ['legal_name' => 'Ignorada']],
            ['company' => ['legal_name' => 'Não altera']],
            ['company' => ['legal_name' => 'Reusa']],
        ]);
        [$first, $second, $third, $fourth] = $import->rows()->orderBy('row_number')->get();
        $this->setDecision($second, 'company', $this->decision('skip'));
        $this->setDecision($third, 'company', $this->decision('use_existing', [['source' => 'crm', 'entity' => 'company', 'id' => $existing->id]]));
        $this->setDecision($fourth, 'company', $this->decision('reuse_import_row', [['source' => 'import', 'entity' => 'company', 'import_row_id' => $first->id, 'row_number' => $first->row_number]]));

        $this->execute($import);

        $rows = $import->rows()->orderBy('row_number')->get();
        $createdId = $rows[0]->execution_data['groups']['company']['entity_id'];
        $this->assertSame('skipped', $rows[1]->execution_data['groups']['company']['result']);
        $this->assertSame($existing->id, $rows[2]->execution_data['groups']['company']['entity_id']);
        $this->assertSame($createdId, $rows[3]->execution_data['groups']['company']['entity_id']);
        $this->assertSame('Existente', $existing->refresh()->legal_name);
    }

    public function test_fiscal_race_is_sanitized_and_does_not_duplicate_company(): void
    {
        $import = $this->executableImport([['company' => ['legal_name' => 'Nova', 'tax_id_country' => 'BR', 'tax_id' => '11222333000181']]]);
        Company::factory()->create(['tax_id_country' => 'BR', 'tax_id' => '11222333000181']);

        $this->execute($import);

        $row = $import->rows()->sole();
        $this->assertSame('failed', $row->execution_data['status']);
        $this->assertSame('strong_duplicate_changed', $row->execution_data['error_code']);
        $this->assertSame(1, Company::withTrashed()->where('tax_id_country', 'BR')->where('tax_id', '11222333000181')->count());
    }

    public function test_blocked_rows_and_double_execution_do_not_create_or_mutate_import_source_data(): void
    {
        $import = $this->executableImport([['company' => ['legal_name' => 'Nova']], ['lead' => ['name' => 'Bloqueado']]]);
        $blocked = $import->rows()->orderBy('row_number')->get()[1];
        $dedup = $blocked->dedup_data;
        $dedup['status'] = 'blocked';
        $blocked->dedup_data = $dedup;
        $blocked->save();
        $beforeOriginal = $import->rows()->pluck('original_data', 'id')->all();
        $beforeNormalized = $import->rows()->pluck('normalized_data', 'id')->all();

        $this->execute($import);
        $this->execute($import);

        $this->assertDatabaseCount('companies', 1);
        $this->assertDatabaseCount('leads', 0);
        $this->assertDatabaseCount('opportunities', 0);
        $this->assertSame($beforeOriginal, $import->rows()->pluck('original_data', 'id')->all());
        $this->assertSame($beforeNormalized, $import->rows()->pluck('normalized_data', 'id')->all());
        $this->assertSame(1, $import->refresh()->imported_rows);
        $this->assertSame(1, $import->failed_rows);
        $this->assertNull($blocked->refresh()->related_entity_id);
    }

    public function test_prerequisites_permissions_processing_lock_and_report_boundary(): void
    {
        $import = $this->executableImport([['company' => ['legal_name' => '<script>alert(1)</script>']]]);
        $viewer = $this->userWithPermission('imports.view');
        $updater = $this->userWithPermission('imports.update');

        $this->actingAs($viewer)->post(route('imports.execute', $import))->assertForbidden();
        $this->actingAs($updater)->get(route('imports.report', $import))->assertForbidden();
        $this->actingAs($viewer)->get(route('imports.report', $import))->assertStatus(409);
        $import->update(['status' => DataImport::STATUS_PROCESSING]);
        try {
            $this->execute($import);
            $this->fail('A execução concorrente deveria ter sido bloqueada.');
        } catch (ImportExecutionException $exception) {
            $this->assertSame('execution_in_progress', $exception->errorCode);
        }
        $import->update(['status' => DataImport::STATUS_PARSED]);
        $this->execute($import);
        $snapshot = $this->businessSnapshot();
        $response = $this->actingAs($viewer)->get(route('imports.report', $import));
        $response->assertOk()->assertDontSee('<script>alert(1)</script>', false);
        $this->assertSame($snapshot, $this->businessSnapshot());
    }

    public function test_execution_audit_is_sanitized_and_counters_keep_dedup_semantics(): void
    {
        $import = $this->executableImport([['company' => ['legal_name' => 'Segredo Comercial']]], duplicateRows: 7);

        $this->execute($import);

        $import->refresh();
        $this->assertSame(7, $import->duplicate_rows);
        $this->assertSame(DataImport::STATUS_COMPLETED, $import->status);
        foreach (['import_execution_started', 'import_row_executed', 'import_execution_completed'] as $action) {
            $this->assertDatabaseHas('audit_logs', ['action' => $action]);
        }
        $this->assertStringNotContainsString('Segredo Comercial', AuditLog::query()->whereIn('action', ['import_execution_started', 'import_row_executed', 'import_execution_completed'])->get()->toJson());
    }

    public function test_long_filename_is_fully_preserved_and_contained_consistently_across_import_views(): void
    {
        $filename = 'template_importacao_crm_compativel_com_nome_extremamente_longo_sem_pontos_de_quebra_1234567890 (2).xlsx';
        $import = $this->executableImport([['company' => ['legal_name' => 'Empresa do relatório']]]);
        $import->update(['original_filename' => $filename]);
        $viewer = $this->userWithPermission('imports.view');

        foreach ([route('imports.preview', $import), route('imports.execute.confirm', $import)] as $url) {
            $this->actingAs($viewer)->get($url)
                ->assertOk()
                ->assertSee($filename)
                ->assertSee('class="min-w-0" data-import-filename', false)
                ->assertSee('class="mt-1 break-words font-semibold', false)
                ->assertDontSee('break-all', false);
        }

        $this->execute($import);

        $this->actingAs($viewer)->get(route('imports.report', $import))
            ->assertOk()
            ->assertSee($filename)
            ->assertSee('class="min-w-0" data-import-filename', false)
            ->assertSee('class="mt-1 break-words font-semibold', false)
            ->assertDontSee('break-all', false);
    }

    public function test_ten_row_batch_reuses_one_complete_record_and_creates_nine_with_linkedin_channel(): void
    {
        $existingCompany = Company::factory()->create(['legal_name' => 'Empresa Existente', 'tax_id_country' => 'BR', 'tax_id' => '11222333000181']);
        $existingContact = Contact::factory()->for($existingCompany)->create(['name' => 'Contato Existente', 'email' => 'existente@example.test']);
        $existingLead = Lead::factory()->create(['name' => 'Lead Existente', 'company_name' => 'Empresa Existente', 'email' => 'lead-existente@example.test']);
        $rows = [[
            'company' => ['legal_name' => $existingCompany->legal_name, 'tax_id_country' => 'BR', 'tax_id' => $existingCompany->tax_id],
            'contact' => ['name' => $existingContact->name, 'email' => $existingContact->email],
            'lead' => ['name' => $existingLead->name, 'company_name' => $existingLead->company_name, 'email' => $existingLead->email],
        ]];
        for ($index = 1; $index <= 9; $index++) {
            $rows[] = [
                'company' => ['legal_name' => "Empresa Nova {$index}"],
                'contact' => ['name' => "Contato Novo {$index}", 'email' => "contato{$index}@example.test"],
                'lead' => ['name' => "Lead Novo {$index}", 'company_name' => "Empresa Nova {$index}", 'email' => "lead{$index}@example.test"],
            ];
        }
        $import = $this->executableImport($rows);
        $officialTargets = [
            'company.trade_name', 'company.legal_name', 'company.tax_id', 'company.tax_id_country', 'company.segment', 'company.category',
            'company.website', 'company.phone', 'company.email', 'company.street', 'company.number', 'company.complement', 'company.district',
            'company.city', 'company.state', 'company.postal_code', 'company.employee_count_estimate', 'company.priority', 'company.notes',
            'contact.name', 'contact.job_title', 'contact.department', 'contact.email', 'contact.phone', 'contact.whatsapp', 'contact.linkedin_url',
            'contact.decision_role', 'contact.influence_level', 'contact.notes',
            'lead.name', 'lead.company_name', 'lead.job_title', 'lead.email', 'lead.phone', 'lead.whatsapp', 'lead.status', 'lead.priority',
            'lead.temperature', 'lead.score', 'lead.notes',
        ];
        $headers = array_map(fn (int $index): string => 'Cabeçalho oficial '.($index + 1), array_keys($officialTargets));
        $metadata = $import->metadata;
        $metadata['header'] = $headers;
        $metadata['mapping'] = ['version' => 1, 'columns' => array_combine($headers, $officialTargets), 'ignored_columns' => []];
        $import->update(['metadata' => $metadata]);
        $this->assertCount(40, $import->refresh()->metadata['mapping']['columns']);
        app(AnalyzeImportDedupAction::class)->execute($import, $this->userWithPermission('imports.update'));

        $summary = $import->refresh()->metadata['dedup']['summary'];
        $this->assertSame(10, $summary['total']);
        $this->assertSame(9, $summary['clear']);
        $this->assertSame(1, $summary['review']);
        $this->assertSame(1, $summary['exact_matches']);
        $this->assertSame(0, $summary['possible_matches']);
        $this->assertSame(0, $summary['blocked']);

        $duplicate = $import->rows()->orderBy('row_number')->firstOrFail();
        $decisionUser = $this->userWithPermission('imports.update');
        foreach (['company', 'contact', 'lead'] as $group) {
            $candidate = collect($duplicate->refresh()->dedup_data['groups'][$group]['candidates'])->firstWhere('source', 'crm');
            $reference = Crypt::encryptString(json_encode(['source' => 'crm', 'entity' => $group, 'id' => $candidate['id']], JSON_THROW_ON_ERROR));
            app(UpdateImportDedupDecisionAction::class)->execute($import, $duplicate, $group, 'use_existing', $reference, $decisionUser);
        }
        $this->assertSame(9, $import->refresh()->metadata['dedup']['summary']['clear']);
        $this->assertSame(1, $import->metadata['dedup']['summary']['resolved']);

        $linkedin = Channel::query()->where('slug', 'linkedin')->firstOrFail();
        $this->execute($import, $linkedin->id);

        $execution = $import->refresh()->metadata['execution']['summary'];
        $this->assertEqualsCanonicalizing(['success' => 9, 'reused' => 1, 'skipped' => 0, 'failed' => 0, 'blocked' => 0], $execution['rows']);
        foreach (['company', 'contact', 'lead'] as $group) {
            $this->assertEqualsCanonicalizing(['created' => 9, 'reused' => 1, 'skipped' => 0], $execution['entities'][$group]);
        }
        $this->assertSame(10, $import->imported_rows);
        $this->assertSame(0, $import->failed_rows);
        $this->assertSame(9, Lead::query()->whereKeyNot($existingLead->id)->where('channel_id', $linkedin->id)->count());
        $this->assertSame('Lead Existente', $existingLead->refresh()->name);
    }

    public function test_company_and_contact_share_a_row_transaction(): void
    {
        $import = $this->executableImport([['company' => ['legal_name' => 'Deve fazer rollback'], 'contact' => ['name' => 'Falha controlada']]]);
        $contactAction = $this->mock(CreateContactAction::class);
        $contactAction->shouldReceive('execute')->once()->andThrow(new RuntimeException('sensitive database detail'));

        $this->execute($import);

        $this->assertDatabaseCount('companies', 0);
        $this->assertDatabaseCount('contacts', 0);
        $execution = $import->rows()->sole()->execution_data;
        $this->assertSame('failed', $execution['status']);
        $this->assertSame('execution_failed', $execution['error_code']);
        $this->assertStringNotContainsString('sensitive database detail', json_encode($execution, JSON_THROW_ON_ERROR));
    }

    public function test_report_is_paginated_ordered_and_filterable(): void
    {
        $rows = array_fill(0, 51, ['company' => ['legal_name' => 'Empresa']]);
        $import = $this->executableImport($rows);
        foreach ($import->rows()->orderBy('row_number')->get() as $index => $row) {
            $row->execution_data = ['version' => 1, 'status' => $index === 50 ? 'failed' : 'success', 'groups' => [], 'error_code' => $index === 50 ? 'execution_failed' : null];
            $row->save();
        }
        $metadata = $import->metadata;
        $metadata['execution'] = ['version' => 1, 'started_by_user_id' => User::factory()->create(['name' => 'Executor Seguro'])->id, 'summary' => ['rows' => ['success' => 50, 'failed' => 1]]];
        $import->update(['status' => DataImport::STATUS_COMPLETED, 'metadata' => $metadata]);
        $viewer = $this->userWithPermission('imports.view');

        $this->actingAs($viewer)->get(route('imports.report', [$import, 'page' => 2]))
            ->assertOk()
            ->assertSee('Executor Seguro')
            ->assertSee('52');
        $this->actingAs($viewer)->get(route('imports.report', [$import, 'status' => 'failed']))
            ->assertOk()
            ->assertSee('Falhou')
            ->assertSee('52');
    }

    public function test_missing_dedup_pending_decision_and_future_dependency_are_rejected(): void
    {
        $missing = $this->executableImport([['company' => ['legal_name' => 'Empresa']]]);
        $metadata = $missing->metadata;
        unset($metadata['dedup']);
        $missing->update(['metadata' => $metadata]);
        try {
            $this->execute($missing);
            $this->fail('Deduplicação ausente deveria bloquear.');
        } catch (ImportExecutionException $exception) {
            $this->assertSame('dedup_required', $exception->errorCode);
        }

        $pending = $this->executableImport([['company' => ['legal_name' => 'Empresa']]]);
        $row = $pending->rows()->sole();
        $this->setDecision($row, 'company', $this->decision('pending'));
        try {
            $this->execute($pending);
            $this->fail('Decisão pendente deveria bloquear.');
        } catch (ImportExecutionException $exception) {
            $this->assertSame('pending_decision', $exception->errorCode);
        }

        $future = $this->executableImport([['company' => ['legal_name' => 'Primeira']], ['company' => ['legal_name' => 'Segunda']]]);
        [$first, $second] = $future->rows()->orderBy('row_number')->get();
        $this->setDecision($first, 'company', $this->decision('reuse_import_row', [['source' => 'import', 'entity' => 'company', 'import_row_id' => $second->id, 'row_number' => $second->row_number]]));
        try {
            $this->execute($future);
            $this->fail('Dependência futura deveria bloquear.');
        } catch (ImportExecutionException $exception) {
            $this->assertSame('invalid_import_dependency', $exception->errorCode);
        }
    }

    public function test_existing_candidates_are_loaded_in_batches_instead_of_per_row(): void
    {
        $leads = Lead::factory()->count(30)->create();
        $rows = [];
        $overrides = [];
        foreach ($leads as $lead) {
            $rows[] = ['lead' => ['name' => 'Não alterar']];
            $overrides[] = ['lead' => $this->decision('use_existing', [['source' => 'crm', 'entity' => 'lead', 'id' => $lead->id]])];
        }
        $import = $this->executableImport($rows, $overrides);
        $leadSelects = 0;
        DB::listen(function ($query) use (&$leadSelects): void {
            if (preg_match('/select .* from "leads"/i', $query->sql) === 1) {
                $leadSelects++;
            }
        });

        $this->execute($import);

        $this->assertLessThanOrEqual(1, $leadSelects);
        $this->assertDatabaseCount('leads', 30);
    }

    public function test_hardened_import_rejects_tampered_normalized_dedup_or_execution_data(): void
    {
        foreach (['normalized', 'dedup', 'execution'] as $tampering) {
            $import = $this->executableImport([['company' => ['legal_name' => 'Empresa íntegra']]]);
            $metadata = $import->metadata;
            $metadata['security'] = ['version' => 1];
            $import->update(['metadata' => $metadata]);
            $metadata = $import->metadata;
            $metadata['security']['dedup_signature'] = app(ImportIntegrityService::class)->dedupSignature($import);
            $import->update(['metadata' => $metadata]);
            $row = $import->rows()->sole();

            if ($tampering === 'normalized') {
                $row->normalized_data = ['company' => ['legal_name' => 'Forjada']];
            } elseif ($tampering === 'dedup') {
                $dedup = $row->dedup_data;
                $dedup['groups']['company']['decision']['action'] = 'skip';
                $row->dedup_data = $dedup;
            } else {
                $row->execution_data = ['version' => 1, 'status' => 'success', 'groups' => []];
            }
            $row->save();

            try {
                $this->execute($import);
                $this->fail("A adulteração de {$tampering} deveria ser bloqueada.");
            } catch (ImportExecutionException $exception) {
                $this->assertContains($exception->errorCode, ['import_data_tampered', 'execution_replay_data']);
            }
            $this->assertDatabaseCount('companies', 0);
        }
    }

    private function execute(DataImport $import, ?int $channelId = null): DataImport
    {
        return app(ExecuteImportAction::class)->execute($import, $channelId, $this->userWithPermission('imports.update'));
    }

    /** @param list<array<string, mixed>> $rows @param list<array<string, mixed>> $overrides */
    private function executableImport(array $rows, array $overrides = [], int $duplicateRows = 0): DataImport
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
        foreach ($targets as $index => $target) {
            $mapping['Campo '.($index + 1)] = $target;
        }
        $import = DataImport::factory()->create([
            'status' => DataImport::STATUS_PARSED,
            'total_rows' => count($rows),
            'duplicate_rows' => $duplicateRows,
            'metadata' => [
                'header' => array_keys($mapping),
                'mapping' => ['version' => 1, 'columns' => $mapping, 'ignored_columns' => []],
                'dedup' => ['version' => 1, 'analyzed_at' => now()->toIso8601String(), 'summary' => ['total' => count($rows)]],
            ],
        ]);
        foreach ($rows as $index => $data) {
            $groups = [];
            foreach (array_keys($data) as $group) {
                $groups[$group] = $overrides[$index][$group] ?? $this->decision('create_new');
            }
            ImportRow::factory()->for($import, 'dataImport')->create([
                'row_number' => $index + 2,
                'original_data' => ['Linha' => 'Dado '.$index],
                'normalized_data' => $data,
                'dedup_data' => ['version' => 1, 'status' => 'resolved', 'groups' => $groups],
            ]);
        }

        return $import;
    }

    /** @param list<array<string, mixed>> $candidates */
    private function decision(string $action, array $candidates = []): array
    {
        $candidate = $candidates[0] ?? [];

        return [
            'match' => $candidates === [] ? 'none' : 'exact',
            'candidates' => $candidates,
            'decision' => [
                'action' => $action,
                'candidate_source' => $candidate['source'] ?? null,
                'candidate_id' => $candidate['id'] ?? $candidate['import_row_id'] ?? null,
            ],
        ];
    }

    private function setDecision(ImportRow $row, string $group, array $groupData): void
    {
        $dedup = $row->dedup_data;
        $dedup['groups'][$group] = $groupData;
        $row->dedup_data = $dedup;
        $row->save();
    }

    /** @return array<string, mixed> */
    private function businessSnapshot(): array
    {
        return [
            'companies' => Company::withTrashed()->orderBy('id')->get()->toArray(),
            'contacts' => Contact::withTrashed()->orderBy('id')->get()->toArray(),
            'leads' => Lead::withTrashed()->orderBy('id')->get()->toArray(),
            'opportunities' => Opportunity::withTrashed()->orderBy('id')->get()->toArray(),
        ];
    }
}
