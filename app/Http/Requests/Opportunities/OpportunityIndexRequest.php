<?php

namespace App\Http\Requests\Opportunities;

use App\Models\Opportunity;
use App\Models\Stage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OpportunityIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Opportunity::class);
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],

            'pipeline_id' => [
                'nullable',
                'integer',
                'exists:pipelines,id',
            ],

            'stage_id' => [
                'nullable',
                'integer',
                'exists:stages,id',
            ],

            'state' => [
                'nullable',
                Rule::in(Stage::TYPES),
            ],

            'assigned_user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],

            'company_id' => [
                'nullable',
                'integer',
                'exists:companies,id',
            ],

            'lead_id' => [
                'nullable',
                'integer',
                'exists:leads,id',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:10',
                'max:100',
            ],
        ];
    }
}
