<?php

namespace App\Http\Requests\Opportunities;

use App\Models\LossReason;
use App\Models\Opportunity;
use App\Models\Stage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreOpportunityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Opportunity::class);
    }

    public function rules(): array
    {
        $pipelineId = $this->integer('pipeline_id') ?: null;
        $companyId = $this->integer('company_id') ?: null;

        $stageExists = Rule::exists('stages', 'id')
            ->where(function ($query) use ($pipelineId): void {
                $query->where('active', true);

                if ($pipelineId !== null) {
                    $query->where('pipeline_id', $pipelineId);
                }
            });

        $contactExists = Rule::exists('contacts', 'id')
            ->where(function ($query) use ($companyId): void {
                $query->whereNull('deleted_at');

                if ($companyId !== null) {
                    $query->where('company_id', $companyId);
                }
            });

        $assignedUserExists = Rule::exists('users', 'id')
            ->where(fn ($query) => $query->where('active', true));

        $lossReasonExists = Rule::exists('loss_reasons', 'id')
            ->where(fn ($query) => $query->where('active', true));

        return [
            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'lead_id' => [
                'nullable',
                'required_without:company_id',
                'integer',
                Rule::exists('leads', 'id')
                    ->where(fn ($query) => $query->whereNull('deleted_at')),
            ],

            'company_id' => [
                'nullable',
                'required_without:lead_id',
                'required_with:contact_id',
                'integer',
                Rule::exists('companies', 'id')
                    ->where(fn ($query) => $query->whereNull('deleted_at')),
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

            'pipeline_id' => [
                'required',
                'integer',
                Rule::exists('pipelines', 'id')
                    ->where(fn ($query) => $query->where('active', true)),
            ],

            'stage_id' => [
                'required',
                'integer',
                $stageExists,
            ],

            'amount' => [
                'required',
                'numeric',
                'min:0',
                'max:9999999999999.99',
            ],

            'currency' => [
                'required',
                'string',
                'size:3',
                'regex:/^[A-Z]{3}$/',
            ],

            'expected_close_date' => [
                'nullable',
                'date',
            ],

            'loss_reason_id' => [
                'nullable',
                'integer',
                $lossReasonExists,
            ],

            'notes' => [
                'nullable',
                'string',
                'max:10000',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('stage_id')) {
                    return;
                }

                $stageId = $this->integer('stage_id');

                if ($stageId <= 0) {
                    return;
                }

                $stage = Stage::query()->find($stageId);

                if ($stage === null) {
                    return;
                }

                $lossReasonId = $this->integer('loss_reason_id') ?: null;

                if ($stage->type === 'lost' && $lossReasonId === null) {
                    $validator->errors()->add(
                        'loss_reason_id',
                        'O motivo da perda é obrigatório para o estágio Perdido.',
                    );
                }

                if ($stage->type !== 'lost' && $lossReasonId !== null) {
                    $validator->errors()->add(
                        'loss_reason_id',
                        'O motivo da perda só pode ser informado para o estágio Perdido.',
                    );
                }

                if (
                    $lossReasonId !== null
                    && ! LossReason::query()
                        ->whereKey($lossReasonId)
                        ->where('active', true)
                        ->exists()
                ) {
                    $validator->errors()->add(
                        'loss_reason_id',
                        'O motivo da perda informado não está disponível.',
                    );
                }
            },
        ];
    }
}
