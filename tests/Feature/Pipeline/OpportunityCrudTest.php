<?php

namespace Tests\Feature\Pipeline;

use App\Actions\Opportunities\CreateOpportunityAction;
use App\Models\Lead;
use App\Models\LossReason;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\Role;
use App\Models\Stage;
use App\Models\User;
use Database\Seeders\LossReasonSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PipelineSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\StageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpportunityCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
            PipelineSeeder::class,
            StageSeeder::class,
            LossReasonSeeder::class,
        ]);

        $this->admin = User::factory()->create();

        $this->admin->roles()->attach(
            Role::query()->where('slug', 'admin')->firstOrFail(),
        );
    }

    public function test_admin_can_create_opportunity(): void
    {
        $pipeline = Pipeline::query()
            ->where('slug', 'comercial')
            ->firstOrFail();

        $stage = Stage::query()
            ->where('pipeline_id', $pipeline->id)
            ->where('slug', 'novo')
            ->firstOrFail();

        $lead = Lead::factory()->create();

        $response = $this
            ->actingAs($this->admin)
            ->post(route('opportunities.store'), [
                'title' => 'ERP Hospital Horizonte',
                'lead_id' => $lead->id,
                'assigned_user_id' => $this->admin->id,
                'pipeline_id' => $pipeline->id,
                'stage_id' => $stage->id,
                'amount' => 50000,
                'currency' => 'BRL',
                'expected_close_date' => now()
                    ->addMonth()
                    ->toDateString(),
                'notes' => 'Oportunidade criada pelo teste.',
            ]);

        $opportunity = Opportunity::query()
            ->where('title', 'ERP Hospital Horizonte')
            ->firstOrFail();

        $response->assertRedirect(
            route('opportunities.show', $opportunity),
        );

        $this->assertSame($pipeline->id, $opportunity->pipeline_id);
        $this->assertSame($stage->id, $opportunity->stage_id);
        $this->assertSame($stage->probability, $opportunity->probability);

        $history = $opportunity->stageHistories()->firstOrFail();

        $this->assertNull($history->from_stage_id);
        $this->assertSame($stage->id, $history->to_stage_id);
        $this->assertSame($this->admin->id, $history->changed_by_user_id);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->admin->id,
            'action' => 'opportunity_created',
            'auditable_id' => $opportunity->id,
        ]);
    }

    public function test_stage_must_belong_to_selected_pipeline(): void
    {
        $pipelineA = Pipeline::query()
            ->where('slug', 'comercial')
            ->firstOrFail();

        $pipelineB = Pipeline::factory()->create();

        $stageB = Stage::factory()->create([
            'pipeline_id' => $pipelineB->id,
        ]);

        $lead = Lead::factory()->create();

        $response = $this
            ->actingAs($this->admin)
            ->post(route('opportunities.store'), [
                'title' => 'Pipeline inválido',
                'lead_id' => $lead->id,
                'pipeline_id' => $pipelineA->id,
                'stage_id' => $stageB->id,
                'amount' => 1000,
                'currency' => 'BRL',
            ]);

        $response->assertSessionHasErrors('stage_id');

        $this->assertDatabaseMissing('opportunities', [
            'title' => 'Pipeline inválido',
        ]);
    }

    public function test_regular_update_cannot_bypass_stage_history(): void
    {
        $pipeline = Pipeline::factory()->create();

        $currentStage = Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'position' => 1,
        ]);

        $targetStage = Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'position' => 2,
        ]);

        $opportunity = Opportunity::factory()->create([
            'pipeline_id' => $pipeline->id,
            'stage_id' => $currentStage->id,
        ]);

        $response = $this
            ->actingAs($this->admin)
            ->put(route('opportunities.update', $opportunity), [
                'title' => 'Título atualizado',
                'lead_id' => $opportunity->lead_id,
                'assigned_user_id' => $this->admin->id,
                'amount' => 75000,
                'currency' => 'BRL',

                // Deve ser ignorado pelo UpdateOpportunityRequest.
                'stage_id' => $targetStage->id,
            ]);

        $response->assertRedirect(
            route('opportunities.show', $opportunity),
        );

        $opportunity->refresh();

        $this->assertSame('Título atualizado', $opportunity->title);
        $this->assertSame($currentStage->id, $opportunity->stage_id);
        $this->assertSame(0, $opportunity->stageHistories()->count());
    }

    public function test_admin_can_move_opportunity_to_lost_with_reason(): void
    {
        $pipeline = Pipeline::query()
            ->where('slug', 'comercial')
            ->firstOrFail();

        $open = Stage::query()
            ->where('pipeline_id', $pipeline->id)
            ->where('slug', 'novo')
            ->firstOrFail();

        $lost = Stage::query()
            ->where('pipeline_id', $pipeline->id)
            ->where('slug', 'perdido')
            ->firstOrFail();

        $reason = LossReason::query()
            ->where('slug', 'concorrente')
            ->firstOrFail();

        $opportunity = Opportunity::factory()->create([
            'pipeline_id' => $pipeline->id,
            'stage_id' => $open->id,
            'probability' => $open->probability,
        ]);

        $response = $this
            ->actingAs($this->admin)
            ->patch(
                route('opportunities.move-stage', $opportunity),
                [
                    'stage_id' => $lost->id,
                    'loss_reason_id' => $reason->id,
                    'notes' => 'Cliente escolheu concorrente.',
                ],
            );

        $response->assertRedirect(
            route('opportunities.show', $opportunity),
        );

        $opportunity->refresh();

        $this->assertSame($lost->id, $opportunity->stage_id);
        $this->assertSame($reason->id, $opportunity->loss_reason_id);
        $this->assertNotNull($opportunity->lost_at);

        $this->assertDatabaseHas('opportunity_stage_histories', [
            'opportunity_id' => $opportunity->id,
            'from_stage_id' => $open->id,
            'to_stage_id' => $lost->id,
            'changed_by_user_id' => $this->admin->id,
        ]);
    }

    public function test_admin_can_open_opportunity_show_with_stage_history(): void
    {
        $pipeline = Pipeline::query()
            ->where('slug', 'comercial')
            ->firstOrFail();

        $stage = Stage::query()
            ->where('pipeline_id', $pipeline->id)
            ->where('slug', 'novo')
            ->firstOrFail();

        $lead = Lead::factory()->create();

        $opportunity = app(
            CreateOpportunityAction::class,
        )->execute([
            'title' => 'Projeto HIS Hospital Vida',
            'lead_id' => $lead->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            'amount' => 80000,
            'currency' => 'BRL',
        ], $this->admin);

        $response = $this
            ->actingAs($this->admin)
            ->get(route('opportunities.show', $opportunity));

        $response
            ->assertOk()
            ->assertSee('Projeto HIS Hospital Vida')
            ->assertSee('Pipeline Comercial')
            ->assertSee('Novo')
            ->assertSee('Histórico do Pipeline')
            ->assertSee('Criação da oportunidade.');
    }

    public function test_ajax_stage_move_returns_json_without_redirect(): void
    {
        $pipeline = Pipeline::query()
            ->where('slug', 'comercial')
            ->firstOrFail();

        $from = Stage::query()
            ->where('pipeline_id', $pipeline->id)
            ->where('slug', 'novo')
            ->firstOrFail();

        $to = Stage::query()
            ->where('pipeline_id', $pipeline->id)
            ->where('slug', 'qualificacao')
            ->firstOrFail();

        $opportunity = Opportunity::factory()->create([
            'pipeline_id' => $pipeline->id,
            'stage_id' => $from->id,
            'probability' => $from->probability,
        ]);

        $response = $this
            ->actingAs($this->admin)
            ->patchJson(
                route('opportunities.move-stage', $opportunity),
                [
                    'stage_id' => $to->id,
                ],
            );

        $response
            ->assertOk()
            ->assertJsonPath('opportunity.stage_id', $to->id)
            ->assertJsonPath('opportunity.probability', $to->probability);

        $opportunity->refresh();

        $this->assertSame($to->id, $opportunity->stage_id);
        $this->assertSame($to->probability, $opportunity->probability);

        $this->assertDatabaseHas('opportunity_stage_histories', [
            'opportunity_id' => $opportunity->id,
            'from_stage_id' => $from->id,
            'to_stage_id' => $to->id,
        ]);
    }

    public function test_admin_can_soft_delete_opportunity(): void
    {
        $opportunity = Opportunity::factory()->create();

        $response = $this
            ->actingAs($this->admin)
            ->delete(route('opportunities.destroy', $opportunity));

        $response->assertRedirect(route('opportunities.index'));

        $this->assertSoftDeleted('opportunities', [
            'id' => $opportunity->id,
        ]);
    }

    public function test_readonly_user_cannot_create_opportunity(): void
    {
        $readonly = User::factory()->create();

        $readonly->roles()->attach(
            Role::query()->where('slug', 'readonly')->firstOrFail(),
        );

        $pipeline = Pipeline::query()
            ->where('slug', 'comercial')
            ->firstOrFail();

        $stage = Stage::query()
            ->where('pipeline_id', $pipeline->id)
            ->where('slug', 'novo')
            ->firstOrFail();

        $lead = Lead::factory()->create();

        $response = $this
            ->actingAs($readonly)
            ->post(route('opportunities.store'), [
                'title' => 'Não autorizado',
                'lead_id' => $lead->id,
                'pipeline_id' => $pipeline->id,
                'stage_id' => $stage->id,
                'amount' => 1000,
                'currency' => 'BRL',
            ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('opportunities', [
            'title' => 'Não autorizado',
        ]);
    }
}
