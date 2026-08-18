<?php

namespace App\Actions\Campaigns;

use App\Models\Campaign;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class UpdateCampaignAction
{
    public function __construct(private readonly AuditService $audit) {}

    /** @param array<string, mixed> $data */
    public function execute(Campaign $campaign, array $data): Campaign
    {
        return DB::transaction(function () use ($campaign, $data): Campaign {
            $before = $campaign->only(array_keys($data));
            $campaign->update($data);
            $campaign->refresh();
            $this->audit->record('campaign_updated', $campaign, before: $before, after: $campaign->only(array_keys($data)));

            return $campaign;
        });
    }
}
