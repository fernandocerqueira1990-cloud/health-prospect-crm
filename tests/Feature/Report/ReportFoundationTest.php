<?php

namespace Tests\Feature\Report;

use App\Models\Lead;
use App\Models\LossReason;
use App\Models\Opportunity;
use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRbac;
use Tests\TestCase;

class ReportFoundationTest extends TestCase
{
    use InteractsWithRbac, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRbac();
    }

    public function test_authorized_user_can_access_real_reports_page(): void
    {
        $this->actingAs($this->userWithPermission('reports.view'))
            ->get('/reports')
            ->assertOk()
            ->assertViewIs('reports.index')
            ->assertSee('Indicadores comerciais e análise de desempenho.')
            ->assertSee('Aplicar')
            ->assertSee('Limpar')
            ->assertDontSee('Módulo em desenvolvimento')
            ->assertDontSee('Em breve')
            ->assertDontSee('Grafana');
    }

    public function test_user_without_reports_permission_is_forbidden_and_does_not_see_navigation_link(): void
    {
        $user = $this->userWithPermission('dashboard.view');

        $this->actingAs($user)->get('/reports')->assertForbidden();
        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('href="'.route('reports.index').'"', false);
    }

    public function test_commercial_summary_uses_real_data_deduplicates_leads_and_classifies_opportunities(): void
    {
        $leads = Lead::factory()->count(4)->create();
        Opportunity::factory()->create(['lead_id' => $leads[0]->id]);
        Opportunity::factory()->create(['lead_id' => $leads[0]->id, 'won_at' => now()]);
        Opportunity::factory()->create([
            'lead_id' => $leads[1]->id,
            'lost_at' => now(),
            'loss_reason_id' => LossReason::factory(),
        ]);

        $response = $this->actingAs($this->userWithPermission('reports.view'))->get('/reports');

        $response->assertOk()
            ->assertSee('Leads criados')
            ->assertSee('Oportunidades abertas')
            ->assertSee('Oportunidades ganhas')
            ->assertSee('Oportunidades perdidas')
            ->assertSee('50,0%')
            ->assertSee('33,3%')
            ->assertViewHas('metrics', fn (array $metrics): bool => $metrics === [
                'leads' => 4,
                'opportunities' => 3,
                'open_opportunities' => 1,
                'won_opportunities' => 1,
                'lost_opportunities' => 1,
                'lead_to_opportunity_conversion' => 50.0,
                'opportunity_to_won_conversion' => 33.3,
            ]);
    }

    public function test_soft_deleted_leads_and_opportunities_are_excluded(): void
    {
        $activeLead = Lead::factory()->create();
        $deletedLead = Lead::factory()->create();
        Opportunity::factory()->create(['lead_id' => $activeLead->id]);
        Opportunity::factory()->create(['lead_id' => $deletedLead->id]);
        $deletedOpportunity = Opportunity::factory()->create(['lead_id' => $activeLead->id, 'won_at' => now()]);

        $deletedLead->delete();
        $deletedOpportunity->delete();

        $this->actingAs($this->userWithPermission('reports.view'))
            ->get('/reports')
            ->assertViewHas('metrics', fn (array $metrics): bool => $metrics['leads'] === 1
                && $metrics['opportunities'] === 2
                && $metrics['open_opportunities'] === 2
                && $metrics['won_opportunities'] === 0
                && $metrics['lead_to_opportunity_conversion'] === 100.0);
    }

    public function test_zero_division_returns_zero_percentages(): void
    {
        $this->actingAs($this->userWithPermission('reports.view'))
            ->get('/reports')
            ->assertViewHas('metrics', fn (array $metrics): bool => $metrics['lead_to_opportunity_conversion'] === 0.0
                && $metrics['opportunity_to_won_conversion'] === 0.0)
            ->assertSee('0,0%');
    }

    public function test_date_from_filters_leads_and_opportunities_inclusively(): void
    {
        $this->createRecordsAt('2026-07-31 12:00:00');
        $this->createRecordsAt('2026-08-01 00:00:00');
        $this->createRecordsAt('2026-08-10 12:00:00');

        $this->assertPeriodMetrics(['date_from' => '2026-08-01'], 2, 2);
    }

    public function test_date_to_filters_leads_and_opportunities_inclusively(): void
    {
        $this->createRecordsAt('2026-08-10 12:00:00');
        $this->createRecordsAt('2026-08-15 23:59:59');
        $this->createRecordsAt('2026-08-16 00:00:00');

        $this->assertPeriodMetrics(['date_to' => '2026-08-15'], 2, 2);
    }

    public function test_combined_period_filters_both_boundaries(): void
    {
        $this->createRecordsAt('2026-08-04 23:59:59');
        $this->createRecordsAt('2026-08-05 00:00:00');
        $this->createRecordsAt('2026-08-15 23:59:59');
        $this->createRecordsAt('2026-08-16 00:00:00');

        $this->assertPeriodMetrics([
            'date_from' => '2026-08-05',
            'date_to' => '2026-08-15',
        ], 2, 2);
    }

    public function test_invalid_or_reversed_period_is_rejected(): void
    {
        $user = $this->userWithPermission('reports.view');

        $this->actingAs($user)
            ->get('/reports?date_from=not-a-date')
            ->assertSessionHasErrors('date_from');

        $this->actingAs($user)
            ->get('/reports?date_from=2026-08-20&date_to=2026-08-19')
            ->assertSessionHasErrors('date_to');
    }

    public function test_reports_permission_and_default_role_assignments_are_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, Permission::where('slug', 'reports.view')->count());
        foreach (['admin', 'tester', 'sales_manager', 'supervisor', 'marketing', 'analyst', 'readonly'] as $role) {
            $this->assertTrue(Role::where('slug', $role)->firstOrFail()->permissions()->where('slug', 'reports.view')->exists());
        }
        $this->assertFalse(Role::where('slug', 'sales_rep')->firstOrFail()->permissions()->where('slug', 'reports.view')->exists());
    }

    private function createRecordsAt(string $timestamp): void
    {
        $lead = Lead::factory()->create(['created_at' => $timestamp, 'updated_at' => $timestamp]);
        Opportunity::factory()->create([
            'lead_id' => $lead->id,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    /** @param array<string, string> $filters */
    private function assertPeriodMetrics(array $filters, int $leads, int $opportunities): void
    {
        $this->actingAs($this->userWithPermission('reports.view'))
            ->get(route('reports.index', $filters))
            ->assertOk()
            ->assertViewHas('metrics', fn (array $metrics): bool => $metrics['leads'] === $leads
                && $metrics['opportunities'] === $opportunities);
    }
}
