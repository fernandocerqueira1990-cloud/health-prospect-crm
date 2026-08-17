<?php

namespace App\Http\Requests\Timeline;

use App\Models\Activity;
use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TimelineIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user->hasPermission('activities.view')
            || $user->hasPermission('tasks.view');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'q' => [
                'nullable',
                'string',
                'max:255',
            ],

            'event_type' => [
                'nullable',
                Rule::in([
                    'activity',
                    'follow_up',
                    'task',
                ]),
            ],

            'channel' => [
                'nullable',
                Rule::in(array_values(array_unique([
                    ...Activity::TYPES,
                    ...Task::FOLLOW_UP_CHANNELS,
                ]))),
            ],

            'status' => [
                'nullable',
                Rule::in(Task::STATUSES),
            ],

            'company_id' => [
                'nullable',
                'integer',
                Rule::exists('companies', 'id'),
            ],

            'contact_id' => [
                'nullable',
                'integer',
                Rule::exists('contacts', 'id'),
            ],

            'lead_id' => [
                'nullable',
                'integer',
                Rule::exists('leads', 'id'),
            ],

            'opportunity_id' => [
                'nullable',
                'integer',
                Rule::exists('opportunities', 'id'),
            ],

            'assigned_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
            ],

            'date_from' => [
                'nullable',
                'date',
            ],

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
