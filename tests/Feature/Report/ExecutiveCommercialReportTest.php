<?php

namespace Tests\Feature\Report;

use App\Models\LossReason;
use App\Models\Opportunity;
use App\Queries\Reports\ExecutiveCommercialQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\InteractsWithRbac;
use Tests\TestCase;

class ExecutiveCommercialReportTest extends TestCase
{
    use InteractsWithRbac, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRbac();
    }

    public function test_financial_metrics_classify_and_aggregate_opportunities_by_currency(): void
    {
        $this->opportunity(['amount' => 50000, 'currency' => 'BRL']);
        $this->opportunity(['amount' => 8000, 'currency' => 'USD']);
        $this->opportunity(['amount' => 10000, 'currency' => 'BRL', 'won_at' => now()]);
        $this->opportunity(['amount' => 20000, 'currency' => 'BRL', 'won_at' => now()]);
        $this->opportunity(['amount' => 3500, 'currency' => 'USD', 'won_at' => now()]);
        $this->opportunity([
            'amount' => 7000,
            'currency' => 'BRL',
            'lost_at' => now(),
            'loss_reason_id' => LossReason::factory(),
        ]);

        $metrics = app(ExecutiveCommercialQuery::class)->get([]);

        $this->assertSame([
            ['currency' => 'BRL', 'amount' => '50000.00'],
            ['currency' => 'USD', 'amount' => '8000.00'],
        ], $metrics['open_pipeline']);
        $this->assertSame([
            ['currency' => 'BRL', 'amount' => '30000.00'],
            ['currency' => 'USD', 'amount' => '3500.00'],
        ], $metrics['won_value']);
        $this->assertSame([
            ['currency' => 'BRL', 'amount' => '7000.00'],
        ], $metrics['lost_value']);
        $this->assertSame([
            ['currency' => 'BRL', 'amount' => '15000.00'],
            ['currency' => 'USD', 'amount' => '3500.00'],
        ], $metrics['average_won_ticket']);
    }

    public function test_soft_deleted_opportunity_is_excluded_from_all_financial_metrics(): void
    {
        $deleted = $this->opportunity(['amount' => 99999, 'won_at' => now()]);
        $deleted->delete();
        $this->opportunity(['amount' => 1250, 'won_at' => now()]);

        $metrics = app(ExecutiveCommercialQuery::class)->get([]);

        $this->assertSame([['currency' => 'BRL', 'amount' => '1250.00']], $metrics['won_value']);
        $this->assertSame([['currency' => 'BRL', 'amount' => '1250.00']], $metrics['average_won_ticket']);
    }

    public function test_null_amount_is_treated_as_zero_without_breaking_aggregates(): void
    {
        DB::statement('ALTER TABLE opportunities ALTER COLUMN amount DROP NOT NULL');
        $this->opportunity(['amount' => null, 'won_at' => now()]);
        $this->opportunity(['amount' => 1000, 'won_at' => now()]);

        $metrics = app(ExecutiveCommercialQuery::class)->get([]);

        $this->assertSame([['currency' => 'BRL', 'amount' => '1000.00']], $metrics['won_value']);
        $this->assertSame([['currency' => 'BRL', 'amount' => '500.00']], $metrics['average_won_ticket']);
    }

    public function test_zero_won_opportunities_has_no_average_and_does_not_divide_by_zero(): void
    {
        $this->opportunity(['amount' => 1000]);

        $metrics = app(ExecutiveCommercialQuery::class)->get([]);

        $this->assertSame([], $metrics['won_value']);
        $this->assertSame([], $metrics['average_won_ticket']);
    }

    public function test_date_from_filters_financial_metrics_by_creation_date_inclusively(): void
    {
        $this->opportunityAt('2026-07-31 23:59:59', 100);
        $this->opportunityAt('2026-08-01 00:00:00', 200);

        $this->assertOpenPipelineFor(['date_from' => '2026-08-01'], '200.00');
    }

    public function test_date_to_filters_financial_metrics_by_creation_date_inclusively(): void
    {
        $this->opportunityAt('2026-08-15 23:59:59', 300);
        $this->opportunityAt('2026-08-16 00:00:00', 400);

        $this->assertOpenPipelineFor(['date_to' => '2026-08-15'], '300.00');
    }

    public function test_combined_period_filters_both_creation_date_boundaries(): void
    {
        $this->opportunityAt('2026-08-04 23:59:59', 100);
        $this->opportunityAt('2026-08-05 00:00:00', 200);
        $this->opportunityAt('2026-08-15 23:59:59', 300);
        $this->opportunityAt('2026-08-16 00:00:00', 400);

        $this->assertOpenPipelineFor([
            'date_from' => '2026-08-05',
            'date_to' => '2026-08-15',
        ], '500.00');
    }

    public function test_report_page_formats_each_currency_and_financial_metric(): void
    {
        $this->opportunity(['amount' => 50000, 'currency' => 'BRL']);
        $this->opportunity(['amount' => 3500, 'currency' => 'USD']);
        $this->opportunity(['amount' => 1200.5, 'currency' => 'BRL', 'won_at' => now()]);
        $this->opportunity([
            'amount' => 700.25,
            'currency' => 'USD',
            'lost_at' => now(),
            'loss_reason_id' => LossReason::factory(),
        ]);

        $this->actingAs($this->userWithPermission('reports.view'))
            ->get('/reports')
            ->assertOk()
            ->assertSee('Visão executiva')
            ->assertSee('Valores comerciais das oportunidades criadas no período.')
            ->assertSee('Pipeline aberto')
            ->assertSee('Valor ganho')
            ->assertSee('Valor perdido')
            ->assertSee('Ticket médio ganho')
            ->assertSee('BRL 50.000,00')
            ->assertSee('USD 3.500,00')
            ->assertSee('BRL 1.200,50')
            ->assertSee('USD 700,25');
    }

    public function test_report_page_presents_empty_financial_state(): void
    {
        $this->actingAs($this->userWithPermission('reports.view'))
            ->get('/reports')
            ->assertOk()
            ->assertSee('Sem valores no período', false, 4);
    }

    public function test_user_without_reports_permission_remains_forbidden(): void
    {
        $this->actingAs($this->userWithPermission('dashboard.view'))
            ->get('/reports')
            ->assertForbidden();
    }

    /** @param array<string, mixed> $attributes */
    private function opportunity(array $attributes = []): Opportunity
    {
        return Opportunity::factory()->create($attributes);
    }

    private function opportunityAt(string $timestamp, float $amount): void
    {
        $this->opportunity([
            'amount' => $amount,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    /** @param array<string, string> $filters */
    private function assertOpenPipelineFor(array $filters, string $amount): void
    {
        $this->actingAs($this->userWithPermission('reports.view'))
            ->get(route('reports.index', $filters))
            ->assertOk()
            ->assertViewHas(
                'executiveMetrics',
                fn (array $metrics): bool => $metrics['open_pipeline'] === [['currency' => 'BRL', 'amount' => $amount]],
            );
    }
}
