<?php

namespace Tests\Feature\Lead;

use App\Models\Channel;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\LeadSourceEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadAttributionTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_can_track_first_and_last_touch_events(): void
    {
        $source = LeadSource::factory()->create([
            'name' => 'Prospecção ativa',
            'slug' => 'prospeccao-ativa',
        ]);

        $linkedin = Channel::factory()->create([
            'name' => 'LinkedIn',
            'slug' => 'linkedin',
        ]);

        $lead = Lead::factory()->create([
            'source_id' => $source->id,
            'channel_id' => $linkedin->id,
            'name' => 'Maria Souza',
            'company_name' => 'Clínica Vida',
            'status' => 'new',
        ]);

        $firstTouch = LeadSourceEvent::factory()->create([
            'lead_id' => $lead->id,
            'source' => 'prospeccao-ativa',
            'channel' => 'linkedin',
            'social_network' => 'linkedin',
            'occurred_at' => now()->subHours(3),
        ]);

        LeadSourceEvent::factory()->create([
            'lead_id' => $lead->id,
            'source' => 'prospeccao-ativa',
            'channel' => 'whatsapp',
            'social_network' => 'whatsapp',
            'occurred_at' => now()->subHours(2),
        ]);

        $lastTouch = LeadSourceEvent::factory()->create([
            'lead_id' => $lead->id,
            'source' => 'prospeccao-ativa',
            'channel' => 'email',
            'occurred_at' => now()->subHour(),
        ]);

        $lead->update([
            'first_touch_source_event_id' => $firstTouch->id,
            'last_touch_source_event_id' => $lastTouch->id,
        ]);

        $lead->refresh();

        $this->assertSame('Prospecção ativa', $lead->source->name);
        $this->assertSame('LinkedIn', $lead->channel->name);

        $this->assertSame(
            $firstTouch->id,
            $lead->firstTouchSourceEvent?->id,
        );

        $this->assertSame(
            $lastTouch->id,
            $lead->lastTouchSourceEvent?->id,
        );

        $this->assertSame('linkedin', $lead->firstTouchSourceEvent?->channel);
        $this->assertSame('email', $lead->lastTouchSourceEvent?->channel);

        $this->assertCount(3, $lead->sourceEvents);
    }

    public function test_deleting_touch_event_nulls_reference_without_deleting_lead(): void
    {
        $lead = Lead::factory()->create();

        $event = LeadSourceEvent::factory()->create([
            'lead_id' => $lead->id,
        ]);

        $lead->update([
            'first_touch_source_event_id' => $event->id,
            'last_touch_source_event_id' => $event->id,
        ]);

        $event->delete();

        $lead->refresh();

        $this->assertNull($lead->first_touch_source_event_id);
        $this->assertNull($lead->last_touch_source_event_id);
        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
        ]);
    }
}
