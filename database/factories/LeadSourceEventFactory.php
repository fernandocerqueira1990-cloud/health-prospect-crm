<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\LeadSourceEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadSourceEvent>
 */
class LeadSourceEventFactory extends Factory
{
    protected $model = LeadSourceEvent::class;

    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'campaign_id' => null,
            'event_type' => 'touch',
            'source' => 'prospeccao-ativa',
            'medium' => 'social',
            'campaign' => null,
            'channel' => 'linkedin',
            'referrer' => null,
            'landing_page' => null,
            'utm_source' => null,
            'utm_medium' => null,
            'utm_campaign' => null,
            'utm_content' => null,
            'utm_term' => null,
            'social_network' => 'linkedin',
            'external_id' => null,
            'occurred_at' => now(),
            'metadata' => null,
        ];
    }
}
