<?php

namespace Tests\Feature\Pipeline;

use App\Actions\Opportunities\MoveOpportunityStageAction;
use App\Models\Lead;
use App\Models\LossReason;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\Stage;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpportunityStageHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_open_stage_move_updates_opportunity_and_creates_history(): void
    {
        $pipeline = Pipeline::factory()->create();

        $from = Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'position' => 1,
            'probability' => 10,
            'type' => 'open',
        ]);

        $to = Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'position' => 2,
            'probability' => 40,
            'type' => 'open',
        ]);

        $user = User::factory()->create();
        $lead = Lead::factory()->create();

        $opportunity = Opportunity::factory()->create([
            'lead_id' => $lead->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $from->id,
            'probability' => 10,
        ]);

        $updated = app(MoveOpportunityStageAction::class)->execute(
            $opportunity,
            $to,
            $user,
            'Avançou após qualificação.',
        );

        $this->assertSame($to->id, $updated->stage_id);
        $this->assertSame(40, $updated->probability);
        $this->assertNull($updated->won_at);
        $this->assertNull($updated->lost_at);

        $this->assertDatabaseHas('opportunity_stage_histories', [
            'pipeline_id' => $pipeline->id,
            'opportunity_id' => $opportunity->id,
            'from_stage_id' => $from->id,
            'to_stage_id' => $to->id,
            'changed_by_user_id' => $user->id,
            'notes' => 'Avançou após qualificação.',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'opportunity_stage_changed',
            'auditable_id' => $opportunity->id,
        ]);
    }

    public function test_moving_to_won_sets_terminal_state(): void
    {
        $pipeline = Pipeline::factory()->create();

        $open = Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'position' => 1,
            'probability' => 70,
            'type' => 'open',
        ]);

        $won = Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'position' => 2,
            'probability' => 100,
            'type' => 'won',
        ]);

        $opportunity = Opportunity::factory()->create([
            'pipeline_id' => $pipeline->id,
            'stage_id' => $open->id,
        ]);

        $updated = app(MoveOpportunityStageAction::class)
            ->execute($opportunity, $won);

        $this->assertSame($won->id, $updated->stage_id);
        $this->assertSame(100, $updated->probability);
        $this->assertNotNull($updated->won_at);
        $this->assertNull($updated->lost_at);
    }

    public function test_moving_to_lost_sets_terminal_state(): void
    {
        $pipeline = Pipeline::factory()->create();

        $open = Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'position' => 1,
            'probability' => 70,
            'type' => 'open',
        ]);

        $lost = Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'position' => 2,
            'probability' => 0,
            'type' => 'lost',
        ]);

        $opportunity = Opportunity::factory()->create([
            'pipeline_id' => $pipeline->id,
            'stage_id' => $open->id,
        ]);

        $lossReason = LossReason::factory()->create();

        $updated = app(MoveOpportunityStageAction::class)
            ->execute(
                $opportunity,
                $lost,
                null,
                null,
                $lossReason,
            );

        $this->assertSame($lost->id, $updated->stage_id);
        $this->assertSame(0, $updated->probability);
        $this->assertNull($updated->won_at);
        $this->assertNotNull($updated->lost_at);
        $this->assertSame($lossReason->id, $updated->loss_reason_id);
    }

    public function test_reopening_terminal_opportunity_clears_terminal_dates(): void
    {
        $pipeline = Pipeline::factory()->create();

        $won = Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'position' => 1,
            'probability' => 100,
            'type' => 'won',
        ]);

        $open = Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'position' => 2,
            'probability' => 30,
            'type' => 'open',
        ]);

        $opportunity = Opportunity::factory()->create([
            'pipeline_id' => $pipeline->id,
            'stage_id' => $won->id,
            'probability' => 100,
            'won_at' => now(),
            'lost_at' => null,
        ]);

        $updated = app(MoveOpportunityStageAction::class)
            ->execute($opportunity, $open);

        $this->assertSame(30, $updated->probability);
        $this->assertNull($updated->won_at);
        $this->assertNull($updated->lost_at);
    }

    public function test_stage_from_another_pipeline_is_rejected_without_history(): void
    {
        $pipelineA = Pipeline::factory()->create();
        $pipelineB = Pipeline::factory()->create();

        $current = Stage::factory()->create([
            'pipeline_id' => $pipelineA->id,
        ]);

        $foreign = Stage::factory()->create([
            'pipeline_id' => $pipelineB->id,
        ]);

        $opportunity = Opportunity::factory()->create([
            'pipeline_id' => $pipelineA->id,
            'stage_id' => $current->id,
        ]);

        try {
            app(MoveOpportunityStageAction::class)
                ->execute($opportunity, $foreign);

            $this->fail('Era esperada DomainException.');
        } catch (DomainException) {
            //
        }

        $opportunity->refresh();

        $this->assertSame($current->id, $opportunity->stage_id);
        $this->assertSame(0, $opportunity->stageHistories()->count());
    }

    public function test_inactive_stage_is_rejected_without_history(): void
    {
        $pipeline = Pipeline::factory()->create();

        $current = Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'position' => 1,
        ]);

        $inactive = Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'position' => 2,
            'active' => false,
        ]);

        $opportunity = Opportunity::factory()->create([
            'pipeline_id' => $pipeline->id,
            'stage_id' => $current->id,
        ]);

        try {
            app(MoveOpportunityStageAction::class)
                ->execute($opportunity, $inactive);

            $this->fail('Era esperada DomainException.');
        } catch (DomainException) {
            //
        }

        $opportunity->refresh();

        $this->assertSame($current->id, $opportunity->stage_id);
        $this->assertSame(0, $opportunity->stageHistories()->count());
    }

    public function test_moving_to_current_stage_is_noop(): void
    {
        $pipeline = Pipeline::factory()->create();

        $stage = Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
        ]);

        $opportunity = Opportunity::factory()->create([
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
        ]);

        app(MoveOpportunityStageAction::class)
            ->execute($opportunity, $stage);

        $this->assertSame(0, $opportunity->stageHistories()->count());

        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'opportunity_stage_changed',
            'auditable_id' => $opportunity->id,
        ]);
    }
}
