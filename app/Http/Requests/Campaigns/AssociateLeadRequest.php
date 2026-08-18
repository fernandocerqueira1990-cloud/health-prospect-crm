<?php

namespace App\Http\Requests\Campaigns;

use App\Models\Campaign;
use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssociateLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $campaign = $this->route('campaign');
        if (! $campaign instanceof Campaign || ! $this->user()?->can('update', $campaign)) {
            return false;
        }

        $lead = Lead::query()->find($this->integer('lead_id'));

        return $lead === null || $this->user()->can('view', $lead);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'lead_id' => ['required', 'integer', Rule::exists('leads', 'id')->whereNull('deleted_at')],
        ];
    }
}
