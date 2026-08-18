<?php

namespace App\Http\Requests\Campaigns;

use App\Models\Campaign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Campaign::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return $this->campaignRules();
    }

    /** @return array<string, mixed> */
    protected function campaignRules(?Campaign $campaign = null): array
    {
        $channelExists = Rule::exists('channels', 'id')->where(
            fn ($query) => $query->where(function ($query) use ($campaign): void {
                $query->where('active', true);
                if ($campaign?->channel_id !== null) {
                    $query->orWhere('id', $campaign->channel_id);
                }
            }),
        );
        $ownerExists = Rule::exists('users', 'id')->where(
            fn ($query) => $query->where(function ($query) use ($campaign): void {
                $query->where('active', true);
                if ($campaign?->owner_user_id !== null) {
                    $query->orWhere('id', $campaign->owner_user_id);
                }
            }),
        );

        return [
            'name' => ['required', 'string', 'max:255', 'regex:/\S/u'],
            'description' => ['nullable', 'string', 'max:10000'],
            'status' => ['required', Rule::in(Campaign::STATUSES)],
            'channel_id' => ['nullable', $channelExists],
            'owner_user_id' => ['nullable', $ownerExists],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'regex:/^[A-Z]{3}$/'],
            'utm_source' => ['nullable', 'string', 'max:255'],
            'utm_medium' => ['nullable', 'string', 'max:255'],
            'utm_campaign' => ['nullable', 'string', 'max:255'],
            'utm_content' => ['nullable', 'string', 'max:255'],
            'utm_term' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
