<?php

namespace Tests\Feature\Lead;

use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRbac;
use Tests\TestCase;

class InactiveLeadTest extends TestCase
{
    use InteractsWithRbac, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRbac();
        config()->set('commercial.lead_inactivity_days', 7);
    }

    public function test_inactive_filter_returns_only_abandoned_open_leads(): void
    {
        $admin = $this->admin();

        $inactive = Lead::factory()->create([
            'name' => 'Lead sem interação',
            'status' => 'new',
            'created_at' => now()->subDays(10),
            'last_interaction_at' => null,
        ]);

        Lead::factory()->create([
            'name' => 'Lead recente',
            'status' => 'new',
            'created_at' => now()->subDays(2),
            'last_interaction_at' => null,
        ]);

        Lead::factory()->create([
            'name' => 'Lead contatado recentemente',
            'status' => 'contacted',
            'created_at' => now()->subDays(15),
            'last_interaction_at' => now()->subDay(),
        ]);

        Lead::factory()->create([
            'name' => 'Lead convertido antigo',
            'status' => 'converted',
            'created_at' => now()->subDays(20),
            'last_interaction_at' => null,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('leads.index', ['inactive' => 1]));

        $response->assertOk()
            ->assertSee($inactive->name)
            ->assertSee('Sem interação há 7+ dias')
            ->assertDontSee('Lead recente')
            ->assertDontSee('Lead contatado recentemente')
            ->assertDontSee('Lead convertido antigo');
    }

    public function test_dashboard_counts_only_current_users_inactive_leads(): void
    {
        $admin = $this->admin();

        Lead::factory()->create([
            'assigned_user_id' => $admin->id,
            'status' => 'nurturing',
            'created_at' => now()->subDays(12),
            'last_interaction_at' => now()->subDays(9),
        ]);

        Lead::factory()->create([
            'assigned_user_id' => $admin->id,
            'status' => 'new',
            'created_at' => now()->subDays(2),
            'last_interaction_at' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Sem interação')
            ->assertSee('Leads sem contato há 7 dias');

        $response->assertViewHas(
            'commercialQueue',
            fn (?array $queue): bool => $queue !== null
                && $queue['inactive_leads'] === 1,
        );
    }
}
