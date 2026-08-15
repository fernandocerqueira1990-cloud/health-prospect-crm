<?php

namespace App\Http\Requests\Activities;

use App\Models\Activity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActivityIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Activity::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],

            'type' => [
                'nullable',
                Rule::in(Activity::TYPES),
            ],

            'direction' => [
                'nullable',
                Rule::in(Activity::DIRECTIONS),
            ],

            'company_id' => [
                'nullable',
                'integer',
                Rule::exists('companies', 'id')
                    ->whereNull('deleted_at'),
            ],

            'contact_id' => [
                'nullable',
                'integer',
                Rule::exists('contacts', 'id')
                    ->whereNull('deleted_at'),
            ],

            'lead_id' => [
                'nullable',
                'integer',
                Rule::exists('leads', 'id')
                    ->whereNull('deleted_at'),
            ],

            'opportunity_id' => [
                'nullable',
                'integer',
                Rule::exists('opportunities', 'id')
                    ->whereNull('deleted_at'),
            ],

            'assigned_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
            ],

            'date_from' => ['nullable', 'date'],
            'date_to' => [
                'nullable',
                'date',
                'after_or_equal:date_from',
            ],

            'per_page' => [
                'nullable',
                'integer',
                Rule::in([15, 30, 50, 100]),
            ],
        ];
    }
}
