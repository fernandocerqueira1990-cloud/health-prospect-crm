<?php

namespace App\Http\Requests\Opportunities;

use App\Models\Opportunity;
use App\Models\Stage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MoveOpportunityStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $opportunity = $this->route('opportunity');

        return $opportunity instanceof Opportunity
            && $this->user()->can('update', $opportunity);
    }

    public function rules(): array
    {
        $opportunity = $this->route('opportunity');

        $pipelineId = $opportunity instanceof Opportunity
            ? $opportunity->pipeline_id
            : null;

        return [
            'stage_id' => [
                'required',
                'integer',
                Rule::exists('stages', 'id')
                    ->where(function ($query) use ($pipelineId): void {
                        $query->where('active', true);

                        if ($pipelineId !== null) {
                            $query->where('pipeline_id', $pipelineId);
                        }
                    }),
            ],

            'loss_reason_id' => [
                'nullable',
                'integer',
                Rule::exists('loss_reasons', 'id')
                    ->where(fn ($query) => $query->where('active', true)),
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
                        'Informe o motivo da perda.',
                    );
                }

                if ($stage->type !== 'lost' && $lossReasonId !== null) {
                    $validator->errors()->add(
                        'loss_reason_id',
                        'Motivo da perda só pode ser informado ao mover para Perdido.',
                    );
                }
            },
        ];
    }
}
