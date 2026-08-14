<?php

namespace App\Http\Requests\Leads;

use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeadIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Lead::class);
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(Lead::STATUSES)],
            'priority' => ['nullable', Rule::in(Lead::PRIORITIES)],
            'temperature' => ['nullable', Rule::in(Lead::TEMPERATURES)],
            'source_id' => ['nullable', 'integer', 'exists:lead_sources,id'],
            'channel_id' => ['nullable', 'integer', 'exists:channels,id'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ];
    }
}
