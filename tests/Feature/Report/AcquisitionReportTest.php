<?php

namespace Tests\Feature\Report;

use App\Models\Campaign;
use App\Models\Channel;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\LeadSourceEvent;
use App\Models\Opportunity;
use App\Queries\Reports\AcquisitionReportQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\InteractsWithRbac;
use Tests\TestCase;

class AcquisitionReportTest extends TestCase
{
    use InteractsWithRbac, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRbac();
    }

    public function test_first_touch_determines_source_and_channel_while_last_touch_does_not_change_them(): void
    {
        LeadSource::query()->where('slug', 'indicacao')->firstOrFail()->update(['name' => 'Indicação']);
        Channel::query()->where('slug', 'whatsapp')->firstOrFail()->update(['name' => 'WhatsApp']);
        $lead = Lead::factory()->create();
        $first = LeadSourceEvent::factory()->create(['lead_id' => $lead->id, 'source' => 'indicacao', 'channel' => 'whatsapp']);
        $last = LeadSourceEvent::factory()->create(['lead_id' => $lead->id, 'source' => 'linkedin', 'channel' => 'social']);
        $lead->update(['first_touch_source_event_id' => $first->id, 'last_touch_source_event_id' => $last->id]);

        $report = $this->acquisitionReport();

        $this->assertSame([['name' => 'Indicação', 'count' => 1, 'percentage' => 100.0]], $report['sources']);
        $this->assertSame([['name' => 'WhatsApp', 'count' => 1, 'percentage' => 100.0]], $report['channels']);

        $this->actingAs($this->userWithPermission('reports.view'))
            ->get('/reports')
            ->assertOk()
            ->assertSee('Indicação')
            ->assertSee('WhatsApp')
            ->assertSee('100,0%');
    }

    public function test_distributions_use_lead_fallback_and_group_missing_data(): void
    {
        $this->allowLegacyMissingAttribution();
        $source = LeadSource::query()->where('slug', 'evento')->firstOrFail();
        $channel = Channel::query()->where('slug', 'presencial')->firstOrFail();
        Lead::factory()->create(['source_id' => $source->id, 'channel_id' => $channel->id]);
        Lead::factory()->create(['source_id' => null, 'channel_id' => null]);

        $report = $this->acquisitionReport();

        $this->assertSame([
            ['name' => 'Evento', 'count' => 1, 'percentage' => 50.0],
            ['name' => 'Não informado', 'count' => 1, 'percentage' => 50.0],
        ], $report['sources']);
        $this->assertSame([
            ['name' => 'Não informado', 'count' => 1, 'percentage' => 50.0],
            ['name' => 'Presencial', 'count' => 1, 'percentage' => 50.0],
        ], $report['channels']);
    }

    public function test_distribution_counts_percentages_and_excludes_soft_deleted_leads(): void
    {
        $this->allowLegacyMissingAttribution();
        $source = LeadSource::query()->where('slug', 'indicacao')->firstOrFail();
        Lead::factory()->count(2)->create(['source_id' => $source->id, 'channel_id' => null]);
        Lead::factory()->create(['source_id' => null, 'channel_id' => null]);
        $deleted = Lead::factory()->create(['source_id' => $source->id]);
        $deleted->delete();

        $sources = $this->acquisitionReport()['sources'];

        $this->assertSame(2, $sources[0]['count']);
        $this->assertSame(66.7, $sources[0]['percentage']);
        $this->assertSame(1, $sources[1]['count']);
        $this->assertSame(33.3, $sources[1]['percentage']);
    }

    public function test_campaign_metrics_deduplicate_touches_allow_multiple_campaigns_and_count_opportunities(): void
    {
        $channel = Channel::factory()->create(['name' => 'LinkedIn']);
        $campaignA = Campaign::factory()->create(['name' => 'Campanha A', 'channel_id' => $channel->id]);
        $campaignB = Campaign::factory()->create(['name' => 'Campanha B']);
        $lead = Lead::factory()->create();
        $leadWithoutOpportunity = Lead::factory()->create();
        $this->attribute($lead, $campaignA, 2);
        $this->attribute($leadWithoutOpportunity, $campaignA);
        $this->attribute($lead, $campaignB);
        Opportunity::factory()->create(['lead_id' => $lead->id]);
        Opportunity::factory()->create(['lead_id' => $lead->id, 'won_at' => now()]);

        $campaigns = $this->acquisitionReport()['campaigns'];

        $this->assertSame(['Campanha A', 'Campanha B'], array_column($campaigns, 'name'));
        $this->assertSame('LinkedIn', $campaigns[0]['channel']);
        $this->assertSame(2, $campaigns[0]['attributed_leads']);
        $this->assertSame(1, $campaigns[0]['leads_with_opportunity']);
        $this->assertSame(2, $campaigns[0]['opportunities']);
        $this->assertSame(1, $campaigns[0]['won_opportunities']);
        $this->assertSame(50.0, $campaigns[0]['lead_to_opportunity_conversion']);
        $this->assertSame(50.0, $campaigns[0]['opportunity_to_won_conversion']);
        $this->assertSame(1, $campaigns[1]['attributed_leads']);
    }

    public function test_campaign_metrics_exclude_soft_deletes_and_protect_zero_division(): void
    {
        $campaign = Campaign::factory()->create(['name' => 'Ativa']);
        $deletedCampaign = Campaign::factory()->create(['name' => 'Arquivada']);
        $lead = Lead::factory()->create();
        $deletedLead = Lead::factory()->create();
        $this->attribute($lead, $campaign);
        $this->attribute($deletedLead, $campaign);
        $this->attribute($lead, $deletedCampaign);
        $deletedLead->delete();
        $deletedCampaign->delete();
        $deletedOpportunity = Opportunity::factory()->create(['lead_id' => $lead->id, 'won_at' => now()]);
        $deletedOpportunity->delete();

        $campaigns = $this->acquisitionReport()['campaigns'];

        $this->assertCount(1, $campaigns);
        $this->assertSame('Ativa', $campaigns[0]['name']);
        $this->assertSame(1, $campaigns[0]['attributed_leads']);
        $this->assertSame(0, $campaigns[0]['opportunities']);
        $this->assertSame(0.0, $campaigns[0]['lead_to_opportunity_conversion']);
        $this->assertSame(0.0, $campaigns[0]['opportunity_to_won_conversion']);
    }

    public function test_period_filters_use_lead_creation_date_inclusively_not_event_date(): void
    {
        $campaign = Campaign::factory()->create();
        foreach (['2026-08-04 23:59:59', '2026-08-05 00:00:00', '2026-08-15 23:59:59', '2026-08-16 00:00:00'] as $timestamp) {
            $lead = Lead::factory()->create(['created_at' => $timestamp, 'updated_at' => $timestamp]);
            $this->attribute($lead, $campaign, occurredAt: '2020-01-01 00:00:00');
        }

        $this->assertPeriodCount(['date_from' => '2026-08-05'], 3);
        $this->assertPeriodCount(['date_to' => '2026-08-15'], 3);
        $this->assertPeriodCount(['date_from' => '2026-08-05', 'date_to' => '2026-08-15'], 2);
    }

    public function test_page_shows_friendly_names_semantics_and_empty_states(): void
    {
        $this->actingAs($this->userWithPermission('reports.view'))
            ->get('/reports')
            ->assertOk()
            ->assertSee('Origem e aquisição')
            ->assertSee('Como os Leads criados no período chegaram ao CRM.')
            ->assertSee('First Touch')
            ->assertSee('Nenhuma origem registrada no período.')
            ->assertSee('Nenhum canal registrado no período.')
            ->assertSee('Nenhuma campanha com Leads atribuídos no período.')
            ->assertDontSee('NaN')
            ->assertDontSee('INF');
    }

    public function test_user_without_reports_permission_is_forbidden(): void
    {
        $this->actingAs($this->userWithPermission('dashboard.view'))->get('/reports')->assertForbidden();
    }

    /** @return array{sources: array<int, array{name: string, count: int, percentage: float}>, channels: array<int, array{name: string, count: int, percentage: float}>, campaigns: array<int, mixed>} */
    private function acquisitionReport(array $filters = []): array
    {
        return app(AcquisitionReportQuery::class)->get($filters);
    }

    private function attribute(Lead $lead, Campaign $campaign, int $count = 1, ?string $occurredAt = null): void
    {
        LeadSourceEvent::factory()->count($count)->create([
            'lead_id' => $lead->id,
            'campaign_id' => $campaign->id,
            'occurred_at' => $occurredAt ?? now(),
        ]);
    }

    /** @param array<string, string> $filters */
    private function assertPeriodCount(array $filters, int $expected): void
    {
        $report = $this->acquisitionReport($filters);
        $this->assertSame($expected, array_sum(array_column($report['sources'], 'count')));
        $this->assertSame($expected, $report['campaigns'][0]['attributed_leads']);
    }

    private function allowLegacyMissingAttribution(): void
    {
        DB::statement('ALTER TABLE leads ALTER COLUMN source_id DROP NOT NULL');
        DB::statement('ALTER TABLE leads ALTER COLUMN channel_id DROP NOT NULL');
    }
}
