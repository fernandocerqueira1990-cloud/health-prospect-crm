<?php

namespace Tests\Feature\Campaign;

use App\Models\Campaign;
use App\Models\Lead;
use App\Models\LeadSourceEvent;
use App\Models\LossReason;
use App\Models\Opportunity;
use App\Models\User;
use App\Queries\CampaignMetricsQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRbac;
use Tests\TestCase;

class CampaignMetricsTest extends TestCase
{
    use InteractsWithRbac;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRbac();
    }

    public function test_campaign_without_leads_returns_zero_metrics(): void
    {
        $metrics = $this->metrics(Campaign::factory()->create());

        $this->assertSame(0, $metrics['attributed_leads']);
        $this->assertSame(0, $metrics['leads_with_opportunity']);
        $this->assertSame(0, $metrics['opportunities']);
        $this->assertSame(0, $metrics['open_opportunities']);
        $this->assertSame(0, $metrics['won_opportunities']);
        $this->assertSame(0, $metrics['lost_opportunities']);
        $this->assertSame(0.0, $metrics['lead_to_opportunity_conversion']);
        $this->assertSame(0.0, $metrics['opportunity_to_won_conversion']);
        $this->assertSame([], $metrics['open_values']);
        $this->assertSame([], $metrics['won_values']);
    }

    public function test_metrics_deduplicate_lead_touches_and_count_opportunities_by_lead_only(): void
    {
        $campaign = Campaign::factory()->create();
        $leadWithOpportunities = Lead::factory()->create();
        $leadWithoutOpportunity = Lead::factory()->create();

        LeadSourceEvent::factory()->count(3)->create([
            'campaign_id' => $campaign->id,
            'lead_id' => $leadWithOpportunities->id,
        ]);
        LeadSourceEvent::factory()->create([
            'campaign_id' => $campaign->id,
            'lead_id' => $leadWithoutOpportunity->id,
        ]);

        Opportunity::factory()->create([
            'lead_id' => $leadWithOpportunities->id,
            'amount' => 10000,
            'currency' => 'BRL',
        ]);
        Opportunity::factory()->create([
            'lead_id' => $leadWithOpportunities->id,
            'amount' => 2500,
            'currency' => 'BRL',
            'won_at' => now(),
        ]);
        Opportunity::factory()->create([
            'lead_id' => $leadWithOpportunities->id,
            'amount' => 700,
            'currency' => 'USD',
            'won_at' => now(),
        ]);
        Opportunity::factory()->create([
            'lead_id' => $leadWithOpportunities->id,
            'amount' => 999,
            'currency' => 'BRL',
            'lost_at' => now(),
            'loss_reason_id' => LossReason::factory(),
        ]);

        $unattributedLead = Lead::factory()->create();
        Opportunity::factory()->create([
            'lead_id' => $unattributedLead->id,
            'company_id' => $leadWithOpportunities->company_id,
            'amount' => 50000,
        ]);

        $metrics = $this->metrics($campaign);

        $this->assertSame(2, $metrics['attributed_leads']);
        $this->assertSame(1, $metrics['leads_with_opportunity']);
        $this->assertSame(4, $metrics['opportunities']);
        $this->assertSame(1, $metrics['open_opportunities']);
        $this->assertSame(2, $metrics['won_opportunities']);
        $this->assertSame(1, $metrics['lost_opportunities']);
        $this->assertSame(50.0, $metrics['lead_to_opportunity_conversion']);
        $this->assertSame(50.0, $metrics['opportunity_to_won_conversion']);
        $this->assertSame([
            ['currency' => 'BRL', 'amount' => '10000.00'],
        ], $metrics['open_values']);
        $this->assertSame([
            ['currency' => 'BRL', 'amount' => '2500.00'],
            ['currency' => 'USD', 'amount' => '700.00'],
        ], $metrics['won_values']);
    }

    public function test_soft_deleted_leads_and_opportunities_are_excluded(): void
    {
        $campaign = Campaign::factory()->create();
        $activeLead = Lead::factory()->create();
        $deletedLead = Lead::factory()->create();
        LeadSourceEvent::factory()->create(['campaign_id' => $campaign->id, 'lead_id' => $activeLead->id]);
        LeadSourceEvent::factory()->create(['campaign_id' => $campaign->id, 'lead_id' => $deletedLead->id]);

        $deletedOpportunity = Opportunity::factory()->create([
            'lead_id' => $activeLead->id,
            'amount' => 1234,
            'won_at' => now(),
        ]);
        $deletedOpportunity->delete();
        Opportunity::factory()->create(['lead_id' => $deletedLead->id, 'amount' => 9000]);
        $deletedLead->delete();

        $metrics = $this->metrics($campaign);

        $this->assertSame(1, $metrics['attributed_leads']);
        $this->assertSame(0, $metrics['opportunities']);
        $this->assertSame(0, $metrics['leads_with_opportunity']);
        $this->assertSame([], $metrics['open_values']);
        $this->assertSame([], $metrics['won_values']);
    }

    public function test_show_renders_metrics_and_preserves_campaign_authorization(): void
    {
        $campaign = Campaign::factory()->create();
        $lead = Lead::factory()->create();
        LeadSourceEvent::factory()->count(2)->create(['campaign_id' => $campaign->id, 'lead_id' => $lead->id]);
        Opportunity::factory()->create(['lead_id' => $lead->id, 'amount' => 25000, 'currency' => 'BRL']);

        $this->actingAs($this->userWithPermission('campaigns.view'))
            ->get(route('campaigns.show', $campaign))
            ->assertOk()
            ->assertSee('Performance da campanha')
            ->assertSee('Lead → Oportunidade')
            ->assertSee('Oportunidade → Ganho')
            ->assertSee('BRL 25.000,00')
            ->assertViewHas('metrics', fn (array $metrics): bool => $metrics['attributed_leads'] === 1
                && $metrics['opportunities'] === 1);

        $this->actingAs(User::factory()->create())
            ->get(route('campaigns.show', $campaign))
            ->assertForbidden();
    }

    /** @return array<string, mixed> */
    private function metrics(Campaign $campaign): array
    {
        return app(CampaignMetricsQuery::class)->get($campaign);
    }
}
