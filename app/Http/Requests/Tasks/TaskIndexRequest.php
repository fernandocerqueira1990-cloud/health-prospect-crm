<?php

namespace App\Http\Requests\Tasks;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Task::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],

            'status' => [
                'nullable',
                Rule::in(Task::STATUSES),
            ],

            'priority' => [
                'nullable',
                Rule::in(Task::PRIORITIES),
            ],

            'assigned_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
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

            'due_from' => [
                'nullable',
                'date',
            ],

            'due_to' => [
                'nullable',
                'date',
                'after_or_equal:due_from',
            ],

            'per_page' => [
                'nullable',
                'integer',
                Rule::in([15, 30, 50, 100]),
            ],
        ];
    }
}
