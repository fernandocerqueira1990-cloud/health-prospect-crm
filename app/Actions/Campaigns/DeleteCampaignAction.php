<?php

namespace App\Actions\Campaigns;

use App\Models\Campaign;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class DeleteCampaignAction
{
    public function __construct(private readonly AuditService $audit) {}

    public function execute(Campaign $campaign): void
    {
        DB::transaction(function () use ($campaign): void {
            $before = $campaign->only(['name', 'status', 'channel_id', 'owner_user_id', 'start_date', 'end_date', 'budget', 'currency']);
            $campaign->delete();
            $this->audit->record('campaign_deleted', $campaign, before: $before);
        });
    }
}
