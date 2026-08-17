<?php

namespace App\Http\Requests\Activities;

use App\Models\Activity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Activity::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return $this->activityRules();
    }

    /** @return array<string, mixed> */
    protected function activityRules(?Activity $activity = null): array
    {
        $assignedUserExists = Rule::exists('users', 'id')
            ->where(function ($query) use ($activity): void {
                $query->where(function ($query) use ($activity): void {
                    $query->where('active', true);

                    if ($activity?->assigned_user_id !== null) {
                        $query->orWhere(
                            'id',
                            $activity->assigned_user_id,
                        );
                    }
                });
            });

        $companyExists = Rule::exists('companies', 'id')
            ->where(function ($query) use ($activity): void {
                $query->where(function ($query) use ($activity): void {
                    $query->whereNull('deleted_at');

                    if ($activity?->company_id !== null) {
                        $query->orWhere(
                            'id',
                            $activity->company_id,
                        );
                    }
                });
            });

        $contactExists = Rule::exists('contacts', 'id')
            ->where(function ($query) use ($activity): void {
                $query->where(function ($query) use ($activity): void {
                    $query->whereNull('deleted_at');

                    if ($activity?->contact_id !== null) {
                        $query->orWhere(
                            'id',
                            $activity->contact_id,
                        );
                    }
                });
            });

        $leadExists = Rule::exists('leads', 'id')
            ->where(function ($query) use ($activity): void {
                $query->where(function ($query) use ($activity): void {
                    $query->whereNull('deleted_at');

                    if ($activity?->lead_id !== null) {
                        $query->orWhere(
                            'id',
                            $activity->lead_id,
                        );
                    }
                });
            });

        $opportunityExists = Rule::exists('opportunities', 'id')
            ->where(function ($query) use ($activity): void {
                $query->where(function ($query) use ($activity): void {
                    $query->whereNull('deleted_at');

                    if ($activity?->opportunity_id !== null) {
                        $query->orWhere(
                            'id',
                            $activity->opportunity_id,
                        );
                    }
                });
            });

        return [
            'type' => [
                'required',
                Rule::in(Activity::TYPES),
            ],

            'direction' => [
                'nullable',
                Rule::in(Activity::DIRECTIONS),
            ],

            'subject' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'outcome' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'company_id' => [
                'nullable',
                'required_without_all:contact_id,lead_id,opportunity_id',
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

            'occurred_at' => [
                'required',
                'date',
            ],

            'duration_minutes' => [
                'nullable',
                'integer',
                'min:1',
                'max:10080',
            ],
        ];
    }
}
