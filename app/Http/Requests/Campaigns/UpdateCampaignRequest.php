<?php

namespace App\Http\Requests\Campaigns;

use App\Models\Campaign;

class UpdateCampaignRequest extends StoreCampaignRequest
{
    public function authorize(): bool
    {
        $campaign = $this->route('campaign');

        return $campaign instanceof Campaign
            && $this->user()->can('update', $campaign);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $campaign = $this->route('campaign');

        return $this->campaignRules($campaign instanceof Campaign ? $campaign : null);
    }
}
