<?php

namespace App\Http\Requests\Campaigns;

use App\Models\Campaign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CampaignIndexRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('q')) {
            $search = trim((string) $this->input('q'));
            $this->merge(['q' => $search === '' ? null : $search]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Campaign::class);
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(Campaign::STATUSES)],
            'channel_id' => ['nullable', 'integer', 'exists:channels,id'],
            'owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'start_date_from' => ['nullable', 'date_format:Y-m-d'],
            'start_date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date_from'],
            'end_date_from' => ['nullable', 'date_format:Y-m-d'],
            'end_date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:end_date_from'],
            'sort' => ['nullable', Rule::in(['name', 'status', 'start_date', 'end_date', 'budget', 'created_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }
}
