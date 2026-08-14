<?php

namespace Tests\Feature\Pipeline;

use App\Actions\Opportunities\CreateOpportunityAction;
use App\Models\Lead;
use App\Models\Pipeline;
use App\Models\Stage;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateOpportunityActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creation_generates_initial_stage_history(): void
    {
        $pipeline = Pipeline::factory()->create();

        $stage = Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'position' => 1,
            'probability' => 20,
            'type' => 'open',
        ]);

        $lead = Lead::factory()->create();
        $user = User::factory()->create();

        $opportunity = app(CreateOpportunityAction::class)->execute([
            'title' => 'ERP Clínica Horizonte',
            'lead_id' => $lead->id,
            'company_id' => null,
            'contact_id' => null,
            'assigned_user_id' => $user->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            'amount' => 25000,
            'currency' => 'BRL',
            'expected_close_date' => now()->addMonth()->toDateString(),
            'notes' => null,
        ], $user);

        $this->assertSame($stage->id, $opportunity->stage_id);
        $this->assertSame(20, $opportunity->probability);

        $history = $opportunity->stageHistories()->firstOrFail();

        $this->assertNull($history->from_stage_id);
        $this->assertSame($stage->id, $history->to_stage_id);
        $this->assertSame($user->id, $history->changed_by_user_id);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'opportunity_created',
            'auditable_id' => $opportunity->id,
        ]);
    }

    public function test_creation_rejects_stage_from_another_pipeline(): void
    {
        $pipelineA = Pipeline::factory()->create();
        $pipelineB = Pipeline::factory()->create();

        $stage = Stage::factory()->create([
            'pipeline_id' => $pipelineB->id,
        ]);

        $lead = Lead::factory()->create();

        $this->expectException(DomainException::class);

        app(CreateOpportunityAction::class)->execute([
            'title' => 'Pipeline incompatível',
            'lead_id' => $lead->id,
            'pipeline_id' => $pipelineA->id,
            'stage_id' => $stage->id,
        ]);
    }

    public function test_creation_rejects_inactive_stage(): void
    {
        $pipeline = Pipeline::factory()->create();

        $stage = Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'active' => false,
        ]);

        $lead = Lead::factory()->create();

        $this->expectException(DomainException::class);

        app(CreateOpportunityAction::class)->execute([
            'title' => 'Stage inativo',
            'lead_id' => $lead->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
        ]);
    }

    public function test_creation_in_won_stage_sets_won_at(): void
    {
        $pipeline = Pipeline::factory()->create();

        $stage = Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'probability' => 100,
            'type' => 'won',
        ]);

        $lead = Lead::factory()->create();

        $opportunity = app(CreateOpportunityAction::class)->execute([
            'title' => 'Venda concluída',
            'lead_id' => $lead->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
        ]);

        $this->assertSame(100, $opportunity->probability);
        $this->assertNotNull($opportunity->won_at);
        $this->assertNull($opportunity->lost_at);
    }
}
