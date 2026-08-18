<?php

namespace App\Actions\Campaigns;

use App\Models\Campaign;
use App\Models\Lead;
use App\Models\LeadSourceEvent;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class AssociateLeadToCampaignAction
{
    public function __construct(private readonly AuditService $audit) {}

    public function execute(Campaign $campaign, Lead $lead, User $actor): LeadSourceEvent
    {
        return DB::transaction(function () use ($campaign, $lead, $actor): LeadSourceEvent {
            $lockedCampaign = Campaign::query()->lockForUpdate()->findOrFail($campaign->id);
            $lockedLead = Lead::query()->lockForUpdate()->findOrFail($lead->id);

            $existing = LeadSourceEvent::query()
                ->where('lead_id', $lockedLead->id)
                ->where('campaign_id', $lockedCampaign->id)
                ->where('event_type', 'campaign_manual_touch')
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            $lockedCampaign->loadMissing('channel:id,slug');
            $event = LeadSourceEvent::create([
                'lead_id' => $lockedLead->id,
                'campaign_id' => $lockedCampaign->id,
                'event_type' => 'campaign_manual_touch',
                'campaign' => $lockedCampaign->utm_campaign ?? $lockedCampaign->name,
                'channel' => $lockedCampaign->channel?->slug,
                'utm_source' => $lockedCampaign->utm_source,
                'utm_medium' => $lockedCampaign->utm_medium,
                'utm_campaign' => $lockedCampaign->utm_campaign,
                'utm_content' => $lockedCampaign->utm_content,
                'utm_term' => $lockedCampaign->utm_term,
                'occurred_at' => now(),
                'metadata' => [
                    'origin' => 'crm',
                    'action' => 'manual_campaign_association',
                ],
            ]);

            $lockedLead->forceFill([
                'first_touch_source_event_id' => $lockedLead->first_touch_source_event_id ?? $event->id,
                'last_touch_source_event_id' => $event->id,
            ])->save();

            $this->audit->record('campaign_lead_associated', $event, after: [
                'campaign_id' => $lockedCampaign->id,
                'lead_id' => $lockedLead->id,
                'source_event_id' => $event->id,
                'event_type' => $event->event_type,
            ], user: $actor);

            return $event;
        });
    }
}
