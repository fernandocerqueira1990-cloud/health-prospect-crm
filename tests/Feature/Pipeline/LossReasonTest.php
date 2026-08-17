<?php

namespace Tests\Feature\Pipeline;

use App\Actions\Opportunities\MoveOpportunityStageAction;
use App\Models\Lead;
use App\Models\LossReason;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\Stage;
use Database\Seeders\LossReasonSeeder;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LossReasonTest extends TestCase
{
    use RefreshDatabase;

    public function test_loss_reason_seeder_is_idempotent(): void
    {
        $this->seed(LossReasonSeeder::class);
        $this->seed(LossReasonSeeder::class);

        $this->assertSame(8, LossReason::query()->count());

        $this->assertSame(
            [
                'preco',
                'sem-orcamento',
                'concorrente',
                'sem-decisao',
                'momento-inadequado',
                'sem-aderencia',
                'sem-contato',
                'outro',
            ],
            LossReason::query()
                ->orderBy('position')
                ->pluck('slug')
                ->all(),
        );
    }

    public function test_moving_to_lost_requires_loss_reason(): void
    {
        $pipeline = Pipeline::factory()->create();

        $open = Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'position' => 1,
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

        $this->expectException(DomainException::class);

        app(MoveOpportunityStageAction::class)
            ->execute($opportunity, $lost);
    }

    public function test_inactive_loss_reason_is_rejected(): void
    {
        $pipeline = Pipeline::factory()->create();

        $open = Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'position' => 1,
            'type' => 'open',
        ]);

        $lost = Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'position' => 2,
            'probability' => 0,
            'type' => 'lost',
        ]);

        $reason = LossReason::factory()->create([
            'active' => false,
        ]);

        $opportunity = Opportunity::factory()->create([
            'pipeline_id' => $pipeline->id,
            'stage_id' => $open->id,
        ]);

        $this->expectException(DomainException::class);

        app(MoveOpportunityStageAction::class)->execute(
            $opportunity,
            $lost,
            null,
            null,
            $reason,
        );
    }

    public function test_loss_reason_cannot_be_used_when_target_stage_is_not_lost(): void
    {
        $pipeline = Pipeline::factory()->create();

        $from = Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'position' => 1,
            'type' => 'open',
        ]);

        $to = Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'position' => 2,
            'type' => 'open',
        ]);

        $reason = LossReason::factory()->create();

        $opportunity = Opportunity::factory()->create([
            'pipeline_id' => $pipeline->id,
            'stage_id' => $from->id,
        ]);

        $this->expectException(DomainException::class);

        app(MoveOpportunityStageAction::class)->execute(
            $opportunity,
            $to,
            null,
            null,
            $reason,
        );
    }

    public function test_reopening_lost_opportunity_clears_loss_reason(): void
    {
        $pipeline = Pipeline::factory()->create();

        $lost = Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'position' => 1,
            'probability' => 0,
            'type' => 'lost',
        ]);

        $open = Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'position' => 2,
            'probability' => 30,
            'type' => 'open',
        ]);

        $reason = LossReason::factory()->create();

        $opportunity = Opportunity::factory()->create([
            'pipeline_id' => $pipeline->id,
            'stage_id' => $lost->id,
            'probability' => 0,
            'lost_at' => now(),
            'loss_reason_id' => $reason->id,
        ]);

        $updated = app(MoveOpportunityStageAction::class)
            ->execute($opportunity, $open);

        $this->assertNull($updated->lost_at);
        $this->assertNull($updated->loss_reason_id);
        $this->assertSame(30, $updated->probability);
    }

    public function test_database_rejects_lost_at_without_loss_reason(): void
    {
        $pipeline = Pipeline::factory()->create();

        $stage = Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
        ]);

        $lead = Lead::factory()->create();

        $this->expectException(QueryException::class);

        Opportunity::create([
            'title' => 'Perdida sem motivo',
            'lead_id' => $lead->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            'lost_at' => now(),
            'loss_reason_id' => null,
        ]);
    }

    public function test_database_rejects_loss_reason_without_lost_at(): void
    {
        $pipeline = Pipeline::factory()->create();

        $stage = Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
        ]);

        $lead = Lead::factory()->create();
        $reason = LossReason::factory()->create();

        $this->expectException(QueryException::class);

        Opportunity::create([
            'title' => 'Aberta com motivo de perda',
            'lead_id' => $lead->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            'lost_at' => null,
            'loss_reason_id' => $reason->id,
        ]);
    }
}
