<?php

namespace App\Http\Requests\Tasks;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Task::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return $this->taskRules();
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (! $this->boolean('is_follow_up')) {
                return;
            }

            $hasCommercialEntity = collect([
                $this->input('company_id'),
                $this->input('contact_id'),
                $this->input('lead_id'),
                $this->input('opportunity_id'),
            ])->filter(
                static fn ($value): bool => $value !== null && $value !== ''
            )->isNotEmpty();

            if (! $hasCommercialEntity) {
                $validator->errors()->add(
                    'company_id',
                    'O follow-up deve estar vinculado a uma entidade comercial.',
                );
            }
        });
    }

    /** @return array<string, mixed> */
    protected function taskRules(?Task $task = null): array
    {
        $assignedUserExists = Rule::exists('users', 'id')
            ->where(function ($query) use ($task): void {
                $query->where(function ($query) use ($task): void {
                    $query->where('active', true);

                    if ($task?->assigned_user_id !== null) {
                        $query->orWhere(
                            'id',
                            $task->assigned_user_id,
                        );
                    }
                });
            });

        $companyExists = Rule::exists('companies', 'id')
            ->where(function ($query) use ($task): void {
                $query->where(function ($query) use ($task): void {
                    $query->whereNull('deleted_at');

                    if ($task?->company_id !== null) {
                        $query->orWhere('id', $task->company_id);
                    }
                });
            });

        $contactExists = Rule::exists('contacts', 'id')
            ->where(function ($query) use ($task): void {
                $query->where(function ($query) use ($task): void {
                    $query->whereNull('deleted_at');

                    if ($task?->contact_id !== null) {
                        $query->orWhere('id', $task->contact_id);
                    }
                });
            });

        $leadExists = Rule::exists('leads', 'id')
            ->where(function ($query) use ($task): void {
                $query->where(function ($query) use ($task): void {
                    $query->whereNull('deleted_at');

                    if ($task?->lead_id !== null) {
                        $query->orWhere('id', $task->lead_id);
                    }
                });
            });

        $opportunityExists = Rule::exists('opportunities', 'id')
            ->where(function ($query) use ($task): void {
                $query->where(function ($query) use ($task): void {
                    $query->whereNull('deleted_at');

                    if ($task?->opportunity_id !== null) {
                        $query->orWhere(
                            'id',
                            $task->opportunity_id,
                        );
                    }
                });
            });

        return [
            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'status' => [
                'required',
                Rule::in(Task::STATUSES),
            ],

            'priority' => [
                'required',
                Rule::in(Task::PRIORITIES),
            ],

            'is_follow_up' => [
                'nullable',
                'boolean',
            ],

            'follow_up_channel' => [
                'nullable',
                'required_if:is_follow_up,1',
                Rule::in(Task::FOLLOW_UP_CHANNELS),
            ],

            'company_id' => [
                'nullable',
                'integer',
                $companyExists,
            ],

            'contact_id' => [
                'nullable',
                'integer',
                $contactExists,
            ],

            'lead_id' => [
                'nullable',
                'integer',
                $leadExists,
            ],

            'opportunity_id' => [
                'nullable',
                'integer',
                $opportunityExists,
            ],

            'assigned_user_id' => [
                'nullable',
                'integer',
                $assignedUserExists,
            ],

            'due_at' => [
                'nullable',
                'required_if:is_follow_up,1',
                'date',
            ],
        ];
    }
}
