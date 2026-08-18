<?php

namespace Tests\Feature\Campaign;

use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\Channel;
use App\Models\Lead;
use App\Models\LeadSourceEvent;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\InteractsWithRbac;
use Tests\TestCase;

class CampaignLeadAttributionTest extends TestCase
{
    use InteractsWithRbac;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRbac();
    }

    public function test_campaign_foreign_key_is_nullable_and_relationships_work(): void
    {
        $this->assertTrue(Schema::hasColumn('lead_source_events', 'campaign_id'));
        $eventWithoutCampaign = LeadSourceEvent::factory()->create(['campaign_id' => null]);
        $campaign = Campaign::factory()->create();
        $event = LeadSourceEvent::factory()->create(['campaign_id' => $campaign->id]);

        $this->assertNull($eventWithoutCampaign->campaign);
        $this->assertTrue($event->campaign()->firstOrFail()->is($campaign));
        $this->assertTrue($campaign->sourceEvents->contains($event));
    }

    public function test_invalid_campaign_foreign_key_is_rejected(): void
    {
        $this->expectException(QueryException::class);
        LeadSourceEvent::factory()->create(['campaign_id' => 999999]);
    }

    public function test_authorized_user_associates_lead_with_campaign_snapshot_and_audit(): void
    {
        $channel = Channel::where('slug', 'email')->firstOrFail();
        $campaign = Campaign::factory()->create([
            'channel_id' => $channel->id,
            'utm_source' => 'newsletter', 'utm_medium' => 'email', 'utm_campaign' => 'winter',
            'utm_content' => 'hero', 'utm_term' => 'health',
        ]);
        $lead = Lead::factory()->create(['first_touch_source_event_id' => null, 'last_touch_source_event_id' => null]);
        $actor = $this->admin();

        $this->actingAs($actor)->post(route('campaigns.leads.store', $campaign), ['lead_id' => $lead->id])
            ->assertRedirect(route('campaigns.show', $campaign))
            ->assertSessionHas('status');

        $event = LeadSourceEvent::whereBelongsTo($campaign)->whereBelongsTo($lead)->sole();
        $this->assertSame('campaign_manual_touch', $event->event_type);
        $this->assertSame('email', $event->channel);
        $this->assertSame(['newsletter', 'email', 'winter', 'hero', 'health'], [
            $event->utm_source, $event->utm_medium, $event->utm_campaign, $event->utm_content, $event->utm_term,
        ]);
        $this->assertEquals(['origin' => 'crm', 'action' => 'manual_campaign_association'], $event->metadata);
        $this->assertNotNull($event->occurred_at);
        $lead->refresh();
        $this->assertSame($event->id, $lead->first_touch_source_event_id);
        $this->assertSame($event->id, $lead->last_touch_source_event_id);

        $audit = AuditLog::where('action', 'campaign_lead_associated')->sole();
        $this->assertSame($actor->id, $audit->user_id);
        $this->assertSame($event->id, $audit->auditable_id);
        $this->assertSame($campaign->id, $audit->after['campaign_id']);
        $this->assertSame($lead->id, $audit->after['lead_id']);
        $this->assertArrayNotHasKey('email', $audit->after);
    }

    public function test_existing_first_touch_is_preserved_and_last_touch_is_updated(): void
    {
        $lead = Lead::factory()->create();
        $first = LeadSourceEvent::factory()->create(['lead_id' => $lead->id]);
        $previousLast = LeadSourceEvent::factory()->create(['lead_id' => $lead->id]);
        $lead->update(['first_touch_source_event_id' => $first->id, 'last_touch_source_event_id' => $previousLast->id]);
        $campaign = Campaign::factory()->create();

        $this->actingAs($this->admin())->post(route('campaigns.leads.store', $campaign), ['lead_id' => $lead->id]);

        $newEvent = LeadSourceEvent::where('campaign_id', $campaign->id)->sole();
        $lead->refresh();
        $this->assertSame($first->id, $lead->first_touch_source_event_id);
        $this->assertSame($newEvent->id, $lead->last_touch_source_event_id);
    }

    public function test_duplicate_manual_association_is_idempotent(): void
    {
        $campaign = Campaign::factory()->create();
        $lead = Lead::factory()->create();
        $actor = $this->admin();

        $this->actingAs($actor)->post(route('campaigns.leads.store', $campaign), ['lead_id' => $lead->id]);
        $firstLastTouch = $lead->fresh()->last_touch_source_event_id;
        $this->actingAs($actor)->post(route('campaigns.leads.store', $campaign), ['lead_id' => $lead->id]);

        $this->assertDatabaseCount('lead_source_events', 1);
        $this->assertDatabaseCount('audit_logs', 1);
        $this->assertSame($firstLastTouch, $lead->fresh()->last_touch_source_event_id);
    }

    public function test_unauthorized_and_idor_requests_are_forbidden(): void
    {
        $campaign = Campaign::factory()->create();
        $lead = Lead::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('campaigns.leads.store', $campaign), ['lead_id' => $lead->id])
            ->assertForbidden();

        $campaignEditorWithoutLeadAccess = $this->userWithPermission('campaigns.update');
        $this->actingAs($campaignEditorWithoutLeadAccess)
            ->post(route('campaigns.leads.store', $campaign), ['lead_id' => $lead->id])
            ->assertForbidden();

        $this->assertDatabaseMissing('lead_source_events', ['campaign_id' => $campaign->id]);
    }

    public function test_missing_and_soft_deleted_leads_are_rejected(): void
    {
        $campaign = Campaign::factory()->create();
        $lead = Lead::factory()->create();
        $lead->delete();
        $actor = $this->admin();

        $this->actingAs($actor)->post(route('campaigns.leads.store', $campaign), ['lead_id' => 999999])
            ->assertSessionHasErrors('lead_id');
        $this->actingAs($actor)->post(route('campaigns.leads.store', $campaign), ['lead_id' => $lead->id])
            ->assertSessionHasErrors('lead_id');
        $this->assertDatabaseCount('lead_source_events', 0);
    }

    public function test_soft_deleted_campaign_does_not_accept_association(): void
    {
        $campaign = Campaign::factory()->create();
        $campaign->delete();

        $this->actingAs($this->admin())
            ->post(route('campaigns.leads.store', $campaign->id), ['lead_id' => Lead::factory()->create()->id])
            ->assertNotFound();
    }

    public function test_show_lists_each_associated_lead_once_and_paginates(): void
    {
        $campaign = Campaign::factory()->create();
        $duplicateLead = Lead::factory()->create(['name' => 'Lead sem duplicação']);
        LeadSourceEvent::factory()->count(2)->create([
            'campaign_id' => $campaign->id,
            'lead_id' => $duplicateLead->id,
            'occurred_at' => now()->addMinute(),
        ]);
        $otherLeads = Lead::factory()->count(15)->create();
        foreach ($otherLeads as $lead) {
            LeadSourceEvent::factory()->create(['campaign_id' => $campaign->id, 'lead_id' => $lead->id]);
        }

        $response = $this->actingAs($this->admin())->get(route('campaigns.show', $campaign));
        $response->assertOk()->assertSee('Leads da campanha')->assertSee('Lead sem duplicação');
        $response->assertViewHas('leads', fn ($leads): bool => $leads->total() === 16 && $leads->count() === 15 && $leads->lastPage() === 2 && $leads->pluck('id')->unique()->count() === 15);
        $this->assertSame(1, substr_count($response->getContent(), 'Lead sem duplicação'));
    }

    public function test_show_search_is_server_side_limited_and_respects_authorization(): void
    {
        Lead::factory()->count(25)->create(['name' => 'Prospect Pesquisavel']);
        $campaign = Campaign::factory()->create();

        $this->actingAs($this->admin())->get(route('campaigns.show', ['campaign' => $campaign, 'lead_q' => 'Pesquisavel']))
            ->assertOk()
            ->assertViewHas('leadOptions', fn ($options): bool => $options->count() === 20);

        $viewer = $this->userWithPermission('campaigns.view');
        $this->actingAs($viewer)->get(route('campaigns.show', ['campaign' => $campaign, 'lead_q' => 'Pesquisavel']))
            ->assertOk()
            ->assertViewHas('leadOptions', fn ($options): bool => $options->isEmpty())
            ->assertDontSee('Associar lead');
    }
}
