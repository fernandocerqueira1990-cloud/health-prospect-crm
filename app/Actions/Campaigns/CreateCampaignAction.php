<?php

namespace App\Actions\Campaigns;

use App\Models\Campaign;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class CreateCampaignAction
{
    public function __construct(private readonly AuditService $audit) {}

    /** @param array<string, mixed> $data */
    public function execute(array $data): Campaign
    {
        return DB::transaction(function () use ($data): Campaign {
            $campaign = Campaign::create($data);
            $this->audit->record('campaign_created', $campaign, after: $data);

            return $campaign;
        });
    }
}
