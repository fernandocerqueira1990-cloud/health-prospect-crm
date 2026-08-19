<?php

namespace Tests\Feature\Report;

use App\Models\LossReason;
use App\Models\Opportunity;
use App\Models\OpportunityStageHistory;
use App\Models\Pipeline;
use App\Models\Stage;
use App\Queries\Reports\PipelineStageTimeQuery;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRbac;
use Tests\TestCase;

class PipelineStageTimeReportTest extends TestCase
{
    use InteractsWithRbac, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRbac();

        CarbonImmutable::setTestNow('2026-08-18 12:00:00');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_uses_opportunity_created_at_when_current_stage_has_no_history(): void
    {
        $pipeline = Pipeline::factory()->create(['name' => 'Comercial']);
        $stage = $this->stage($pipeline, 'Novo', 1);

        Opportunity::factory()->create([
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            'created_at' => '2026-08-17 12:00:00',
            'updated_at' => '2026-08-17 12:00:00',
        ]);

        $result = app(PipelineStageTimeQuery::class)->get([]);

        $this->assertCount(1, $result);
        $this->assertSame('Comercial', $result[0]['pipeline_name']);
        $this->assertSame(1, $result[0]['total_open']);
        $this->assertSame('Novo', $result[0]['stages'][0]['stage_name']);
        $this->assertSame(24.0, $result[0]['stages'][0]['average_hours']);
        $this->assertSame(24.0, $result[0]['stages'][0]['max_hours']);
    }

    public function test_uses_latest_entry_into_current_stage(): void
    {
        $pipeline = Pipeline::factory()->create(['name' => 'Comercial']);
        $new = $this->stage($pipeline, 'Novo', 1);
        $qualification = $this->stage($pipeline, 'Qualificação', 2);

        $opportunity = Opportunity::factory()->create([
            'pipeline_id' => $pipeline->id,
            'stage_id' => $qualification->id,
            'created_at' => '2026-08-10 12:00:00',
            'updated_at' => '2026-08-10 12:00:00',
        ]);

        OpportunityStageHistory::factory()->create([
            'pipeline_id' => $pipeline->id,
            'opportunity_id' => $opportunity->id,
            'from_stage_id' => $new->id,
            'to_stage_id' => $qualification->id,
            'changed_at' => '2026-08-18 06:00:00',
        ]);

        $result = app(PipelineStageTimeQuery::class)->get([]);

        $this->assertSame('Qualificação', $result[0]['stages'][0]['stage_name']);
        $this->assertSame(6.0, $result[0]['stages'][0]['average_hours']);
        $this->assertSame(6.0, $result[0]['stages'][0]['max_hours']);
    }

    public function test_latest_reentry_into_same_stage_wins(): void
    {
        $pipeline = Pipeline::factory()->create();
        $stageA = $this->stage($pipeline, 'Novo', 1);
        $stageB = $this->stage($pipeline, 'Qualificação', 2);

        $opportunity = Opportunity::factory()->create([
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stageA->id,
            'created_at' => '2026-08-10 12:00:00',
        ]);

        OpportunityStageHistory::factory()->create([
            'pipeline_id' => $pipeline->id,
            'opportunity_id' => $opportunity->id,
            'from_stage_id' => $stageB->id,
            'to_stage_id' => $stageA->id,
            'changed_at' => '2026-08-16 12:00:00',
        ]);

        OpportunityStageHistory::factory()->create([
            'pipeline_id' => $pipeline->id,
            'opportunity_id' => $opportunity->id,
            'from_stage_id' => $stageB->id,
            'to_stage_id' => $stageA->id,
            'changed_at' => '2026-08-18 09:00:00',
        ]);

        $result = app(PipelineStageTimeQuery::class)->get([]);

        $this->assertSame(3.0, $result[0]['stages'][0]['average_hours']);
    }

    public function test_aggregates_average_and_max_time_per_stage(): void
    {
        $pipeline = Pipeline::factory()->create();
        $stage = $this->stage($pipeline, 'Novo', 1);

        Opportunity::factory()->create([
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            'created_at' => '2026-08-18 06:00:00',
        ]);

        Opportunity::factory()->create([
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            'created_at' => '2026-08-18 10:00:00',
        ]);

        $result = app(PipelineStageTimeQuery::class)->get([]);

        $stageResult = $result[0]['stages'][0];

        $this->assertSame(2, $stageResult['opportunities']);
        $this->assertSame(4.0, $stageResult['average_hours']);
        $this->assertSame(6.0, $stageResult['max_hours']);
    }

    public function test_closed_opportunities_are_excluded(): void
    {
        $pipeline = Pipeline::factory()->create();
        $stage = $this->stage($pipeline, 'Novo', 1);

        Opportunity::factory()->create([
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
        ]);

        Opportunity::factory()->create([
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            'won_at' => now(),
        ]);

        Opportunity::factory()->create([
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            'lost_at' => now(),
            'loss_reason_id' => LossReason::factory(),
        ]);

        $result = app(PipelineStageTimeQuery::class)->get([]);

        $this->assertSame(1, $result[0]['total_open']);
    }

    public function test_soft_deleted_opportunity_is_excluded(): void
    {
        $pipeline = Pipeline::factory()->create();
        $stage = $this->stage($pipeline, 'Novo', 1);

        $active = Opportunity::factory()->create([
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
        ]);

        $deleted = Opportunity::factory()->create([
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
        ]);

        $deleted->delete();

        $result = app(PipelineStageTimeQuery::class)->get([]);

        $this->assertSame($active->id !== null ? 1 : 0, $result[0]['total_open']);
    }

    public function test_period_filters_use_opportunity_creation_date(): void
    {
        $pipeline = Pipeline::factory()->create();
        $stage = $this->stage($pipeline, 'Novo', 1);

        foreach ([
            '2026-08-04 23:59:59',
            '2026-08-05 00:00:00',
            '2026-08-15 23:59:59',
            '2026-08-16 00:00:00',
        ] as $timestamp) {
            Opportunity::factory()->create([
                'pipeline_id' => $pipeline->id,
                'stage_id' => $stage->id,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }

        $query = app(PipelineStageTimeQuery::class);

        $this->assertSame(
            3,
            $query->get(['date_from' => '2026-08-05'])[0]['total_open'],
        );

        $this->assertSame(
            3,
            $query->get(['date_to' => '2026-08-15'])[0]['total_open'],
        );

        $this->assertSame(
            2,
            $query->get([
                'date_from' => '2026-08-05',
                'date_to' => '2026-08-15',
            ])[0]['total_open'],
        );
    }

    public function test_multiple_pipelines_are_separated_and_stages_ordered(): void
    {
        $pipelineB = Pipeline::factory()->create(['name' => 'Beta']);
        $pipelineA = Pipeline::factory()->create(['name' => 'Alpha']);

        $stageB = $this->stage($pipelineB, 'Etapa B', 2);
        $stageA2 = $this->stage($pipelineA, 'Segunda', 2);
        $stageA1 = $this->stage($pipelineA, 'Primeira', 1);

        Opportunity::factory()->create([
            'pipeline_id' => $pipelineB->id,
            'stage_id' => $stageB->id,
        ]);

        Opportunity::factory()->create([
            'pipeline_id' => $pipelineA->id,
            'stage_id' => $stageA2->id,
        ]);

        Opportunity::factory()->create([
            'pipeline_id' => $pipelineA->id,
            'stage_id' => $stageA1->id,
        ]);

        $result = app(PipelineStageTimeQuery::class)->get([]);

        $this->assertSame(
            ['Alpha', 'Beta'],
            array_column($result, 'pipeline_name'),
        );

        $this->assertSame(
            ['Primeira', 'Segunda'],
            array_column($result[0]['stages'], 'stage_name'),
        );
    }

    public function test_empty_state_and_permission_are_preserved(): void
    {
        $this->actingAs($this->userWithPermission('reports.view'))
            ->get('/reports')
            ->assertOk()
            ->assertSee('Tempo por etapa')
            ->assertSee('Nenhuma oportunidade aberta encontrada no período selecionado.');

        $this->actingAs($this->userWithPermission('dashboard.view'))
            ->get('/reports')
            ->assertForbidden();
    }

    private function stage(
        Pipeline $pipeline,
        string $name,
        int $position,
    ): Stage {
        return Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'name' => $name,
            'position' => $position,
            'type' => 'open',
            'active' => true,
        ]);
    }
}
