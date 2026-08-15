<?php

namespace Tests\Feature\Dashboard;

use App\Models\Company;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRbac;
use Tests\TestCase;

class DashboardShellTest extends TestCase
{
    use InteractsWithRbac, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRbac();
    }

    public function test_admin_dashboard_exposes_authorized_commercial_metrics_and_recent_records(): void
    {
        $admin = $this->admin();
        $company = Company::factory()->create([
            'legal_name' => 'Hospital Horizonte',
            'trade_name' => null,
            'priority' => 'critical',
        ]);
        Company::factory()->create(['priority' => 'low']);
        Contact::factory()->create([
            'company_id' => $company->id,
            'name' => 'Maria Decisora',
            'decision_role' => 'decision_maker',
            'active' => true,
        ]);
        Contact::factory()->create([
            'company_id' => $company->id,
            'decision_role' => 'technical',
            'active' => false,
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Central comercial')
            ->assertSee('Hospital Horizonte')
            ->assertSee('Maria Decisora')
            ->assertSee('Sprint 4 — Leads');

        $response->assertViewHas('stats', fn (array $stats): bool => $stats['companies'] === 2
            && $stats['high_priority_companies'] === 1
            && $stats['active_contacts'] === 1
            && $stats['decision_contacts'] === 1
        );
    }

    public function test_dashboard_does_not_expose_company_or_contact_data_without_module_permissions(): void
    {
        Company::factory()->create(['legal_name' => 'Empresa Restrita']);
        Contact::factory()->create(['name' => 'Contato Restrito']);
        $user = $this->userWithPermission('dashboard.view');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()
            ->assertDontSee('Empresa Restrita')
            ->assertDontSee('Contato Restrito');

        $response->assertViewHas('stats', fn (array $stats): bool => $stats['companies'] === null
            && $stats['high_priority_companies'] === null
            && $stats['active_contacts'] === null
            && $stats['decision_contacts'] === null
        );
    }

    public function test_authenticated_active_user_can_open_leads_and_future_module_placeholders(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('leads.index'))
            ->assertOk();

        foreach ([
            'roadmap.pipeline',
            'activities.index',
            'tasks.index',
            'roadmap.campaigns',
            'roadmap.reports',
        ] as $routeName) {
            $this->actingAs($admin)
                ->get(route($routeName))
                ->assertOk();
        }
    }
}
