<?php

namespace Tests\Feature\Report;

use App\Models\LossReason;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\Stage;
use App\Queries\Reports\FunnelReportQuery;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\InteractsWithRbac;
use Tests\TestCase;

class FunnelReportTest extends TestCase
{
    use InteractsWithRbac, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRbac();
    }

    public function test_funnel_orders_stages_and_calculates_stage_distribution_and_conversions(): void
    {
        $pipeline = Pipeline::factory()->create(['name' => 'Comercial']);
        $proposal = $this->stage($pipeline, 'Proposta', 20);
        $qualification = $this->stage($pipeline, 'Qualificação', 10);
        $won = $this->stage($pipeline, 'Ganho', 30, 'won');
        $lost = $this->stage($pipeline, 'Perdido', 40, 'lost');

        $this->opportunities($pipeline, $qualification, 4);
        $this->opportunities($pipeline, $proposal, 2);
        $this->opportunities($pipeline, $won, 1, ['won_at' => now()]);
        $this->opportunities($pipeline, $lost, 1, [
            'lost_at' => now(),
            'loss_reason_id' => LossReason::factory(),
        ]);

        $funnel = app(FunnelReportQuery::class)->get([])[0];

        $this->assertSame('Comercial', $funnel['name']);
        $this->assertSame(8, $funnel['total']);
        $this->assertSame(['Qualificação', 'Proposta', 'Ganho', 'Perdido'], array_column($funnel['stages'], 'name'));
        $this->assertSame([4, 2, 1, 1], array_column($funnel['stages'], 'count'));
        $this->assertSame([50.0, 25.0, 12.5, 12.5], array_column($funnel['stages'], 'percentage'));
        $this->assertSame(6, $funnel['open']);
        $this->assertSame(1, $funnel['won']);
        $this->assertSame(1, $funnel['lost']);
        $this->assertSame(75.0, $funnel['open_rate']);
        $this->assertSame(12.5, $funnel['win_rate']);
        $this->assertSame(12.5, $funnel['loss_rate']);
    }

    public function test_multiple_pipelines_are_deterministically_separated_without_mixing_stages(): void
    {
        $pipelineZ = Pipeline::factory()->create(['name' => 'Zeta']);
        $pipelineA = Pipeline::factory()->create(['name' => 'Alpha']);
        $stageZ = $this->stage($pipelineZ, 'Etapa Z', 1);
        $unusedZ = $this->stage($pipelineZ, 'Sem dados', 2, active: false);
        $stageA = $this->stage($pipelineA, 'Etapa A', 1);
        $inactiveA = $this->stage($pipelineA, 'Legada', 2, active: false);

        $this->opportunities($pipelineZ, $stageZ, 2);
        $this->opportunities($pipelineA, $stageA, 1);
        $this->opportunities($pipelineA, $inactiveA, 1);

        $funnels = app(FunnelReportQuery::class)->get([]);

        $this->assertSame(['Alpha', 'Zeta'], array_column($funnels, 'name'));
        $this->assertSame(['Etapa A', 'Legada'], array_column($funnels[0]['stages'], 'name'));
        $this->assertSame(['Etapa Z'], array_column($funnels[1]['stages'], 'name'));
        $this->assertFalse(collect($funnels)->contains(fn (array $funnel): bool => collect($funnel['stages'])->contains('id', $unusedZ->id)));
    }

    public function test_soft_deleted_opportunity_is_excluded(): void
    {
        $pipeline = Pipeline::factory()->create();
        $stage = $this->stage($pipeline, 'Novo', 1);
        $this->opportunities($pipeline, $stage, 1);
        $deleted = $this->opportunities($pipeline, $stage, 1)->firstOrFail();
        $deleted->delete();

        $this->assertSame(1, app(FunnelReportQuery::class)->get([])[0]['total']);
    }

    public function test_date_filters_use_opportunity_creation_date_inclusively(): void
    {
        $pipeline = Pipeline::factory()->create();
        $stage = $this->stage($pipeline, 'Novo', 1);
        foreach (['2026-08-04 23:59:59', '2026-08-05 00:00:00', '2026-08-15 23:59:59', '2026-08-16 00:00:00'] as $timestamp) {
            $this->opportunities($pipeline, $stage, 1, ['created_at' => $timestamp, 'updated_at' => $timestamp]);
        }

        $query = app(FunnelReportQuery::class);

        $this->assertSame(3, $query->get(['date_from' => '2026-08-05'])[0]['total']);
        $this->assertSame(3, $query->get(['date_to' => '2026-08-15'])[0]['total']);
        $this->assertSame(2, $query->get(['date_from' => '2026-08-05', 'date_to' => '2026-08-15'])[0]['total']);
    }

    public function test_empty_funnel_protects_zero_division_and_page_shows_empty_state(): void
    {
        $this->assertSame([], app(FunnelReportQuery::class)->get([]));

        $this->actingAs($this->userWithPermission('reports.view'))
            ->get('/reports')
            ->assertOk()
            ->assertSee('Nenhuma oportunidade encontrada no período selecionado.')
            ->assertDontSee('NaN')
            ->assertDontSee('INF');
    }

    public function test_page_exposes_funnel_semantics_stage_names_and_percentages(): void
    {
        $pipeline = Pipeline::factory()->create(['name' => 'Pipeline Saúde']);
        $stage = $this->stage($pipeline, 'Diagnóstico', 1);
        $this->opportunities($pipeline, $stage, 1);

        $this->actingAs($this->userWithPermission('reports.view'))
            ->get('/reports')
            ->assertOk()
            ->assertSee('Funil comercial')
            ->assertSee('Distribuição atual das oportunidades criadas no período pelas etapas do pipeline.')
            ->assertSee('Esta é uma fotografia das etapas atuais, não uma reconstrução histórica do período.')
            ->assertSee('Pipeline Saúde')
            ->assertSee('Diagnóstico')
            ->assertSee('100,0%')
            ->assertSee('Taxa em aberto')
            ->assertSee('Taxa de ganho')
            ->assertSee('Taxa de perda');
    }

    public function test_legacy_opportunity_without_stage_or_pipeline_does_not_break_report(): void
    {
        DB::statement('ALTER TABLE opportunities DROP CONSTRAINT opportunities_pipeline_id_stage_id_foreign');
        DB::statement('ALTER TABLE opportunities DROP CONSTRAINT opportunities_pipeline_id_foreign');
        DB::statement('ALTER TABLE opportunities ALTER COLUMN pipeline_id DROP NOT NULL');
        DB::statement('ALTER TABLE opportunities ALTER COLUMN stage_id DROP NOT NULL');

        $pipeline = Pipeline::factory()->create(['name' => 'Comercial']);
        $stage = $this->stage($pipeline, 'Novo', 1);
        Opportunity::factory()->create(['pipeline_id' => $pipeline->id, 'stage_id' => null]);
        Opportunity::factory()->create(['pipeline_id' => null, 'stage_id' => null]);

        $funnels = app(FunnelReportQuery::class)->get([]);

        $this->assertSame(['Comercial', 'Pipeline não informado'], array_column($funnels, 'name'));
        $this->assertSame('Etapa não informada', $funnels[0]['stages'][1]['name']);
        $this->assertSame('Etapa não informada', $funnels[1]['stages'][0]['name']);
        $this->assertSame(0, $funnels[0]['stages'][0]['count']);
    }

    public function test_user_without_reports_permission_remains_forbidden(): void
    {
        $this->actingAs($this->userWithPermission('dashboard.view'))
            ->get('/reports')
            ->assertForbidden();
    }

    private function stage(Pipeline $pipeline, string $name, int $position, string $type = 'open', bool $active = true): Stage
    {
        return Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'name' => $name,
            'position' => $position,
            'type' => $type,
            'active' => $active,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return Collection<int, Opportunity>
     */
    private function opportunities(Pipeline $pipeline, Stage $stage, int $count, array $attributes = []): Collection
    {
        return Opportunity::factory()->count($count)->create([
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            ...$attributes,
        ]);
    }
}
