<?php

namespace App\Http\Requests\Leads;

use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Lead::class);
    }

    public function rules(): array
    {
        return $this->leadRules();
    }

    /** @return array<string, mixed> */
    protected function leadRules(?Lead $lead = null): array
    {
        $companyId = $this->integer('company_id') ?: null;

        $sourceExists = Rule::exists('lead_sources', 'id')
            ->where(function ($query) use ($lead): void {
                $query->where(function ($query) use ($lead): void {
                    $query->where('active', true);

                    if ($lead !== null) {
                        $query->orWhere('id', $lead->source_id);
                    }
                });
            });

        $channelExists = Rule::exists('channels', 'id')
            ->where(function ($query) use ($lead): void {
                $query->where(function ($query) use ($lead): void {
                    $query->where('active', true);

                    if ($lead !== null) {
                        $query->orWhere('id', $lead->channel_id);
                    }
                });
            });

        $assignedUserExists = Rule::exists('users', 'id')
            ->where(function ($query) use ($lead): void {
                $query->where(function ($query) use ($lead): void {
                    $query->where('active', true);

                    if ($lead?->assigned_user_id !== null) {
                        $query->orWhere('id', $lead->assigned_user_id);
                    }
                });
            });

        $companyExists = Rule::exists('companies', 'id')
            ->where(fn ($query) => $query->whereNull('deleted_at'));

        $contactExists = Rule::exists('contacts', 'id')
            ->where(function ($query) use ($companyId): void {
                $query->whereNull('deleted_at');

                if ($companyId !== null) {
                    $query->where('company_id', $companyId);
                }
            });

        return [
            'name' => [
                'nullable',
                'required_without_all:company_name,email,phone,whatsapp,company_id,contact_id',
                'string',
                'max:255',
            ],

            'company_name' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'whatsapp' => ['nullable', 'string', 'max:64'],

            'company_id' => [
                'nullable',
                'required_with:contact_id',
                'integer',
                $companyExists,
            ],

            'contact_id' => [
                'nullable',
                'integer',
                $contactExists,
            ],

            'assigned_user_id' => [
                'nullable',
                'integer',
                $assignedUserExists,
            ],

            'source_id' => [
                'required',
                'integer',
                $sourceExists,
            ],

            'channel_id' => [
                'required',
                'integer',
                $channelExists,
            ],

            'status' => [
                'required',
                Rule::in(Lead::STATUSES),
            ],

            'priority' => [
                'nullable',
                Rule::in(Lead::PRIORITIES),
            ],

            'temperature' => [
                'nullable',
                Rule::in(Lead::TEMPERATURES),
            ],

            'score' => [
                'required',
                'integer',
                'min:0',
                'max:100',
            ],

            'qualified_at' => ['nullable', 'date'],
            'converted_at' => ['nullable', 'date'],
            'lost_at' => ['nullable', 'date'],
            'last_interaction_at' => ['nullable', 'date'],
            'next_action_at' => ['nullable', 'date'],

            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
