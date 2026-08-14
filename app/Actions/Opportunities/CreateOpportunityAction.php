<?php

namespace App\Actions\Opportunities;

use App\Models\LossReason;
use App\Models\Opportunity;
use App\Models\OpportunityStageHistory;
use App\Models\Stage;
use App\Models\User;
use App\Services\AuditService;
use DomainException;
use Illuminate\Support\Facades\DB;

class CreateOpportunityAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    /** @param array<string, mixed> $data */
    public function execute(
        array $data,
        ?User $createdBy = null,
    ): Opportunity {
        return DB::transaction(function () use ($data, $createdBy): Opportunity {
            $stage = Stage::query()
                ->findOrFail($data['stage_id']);

            if (! $stage->active) {
                throw new DomainException(
                    'Não é permitido criar oportunidade em estágio inativo.',
                );
            }

            if ((int) $data['pipeline_id'] !== $stage->pipeline_id) {
                throw new DomainException(
                    'O estágio inicial não pertence ao pipeline informado.',
                );
            }

            $lossReason = null;
            $lossReasonId = $data['loss_reason_id'] ?? null;

            if ($stage->type === 'lost') {
                if ($lossReasonId === null) {
                    throw new DomainException(
                        'O motivo da perda é obrigatório.',
                    );
                }

                $lossReason = LossReason::query()
                    ->findOrFail($lossReasonId);

                if (! $lossReason->active) {
                    throw new DomainException(
                        'O motivo da perda informado está inativo.',
                    );
                }
            } elseif ($lossReasonId !== null) {
                throw new DomainException(
                    'Motivo da perda só pode ser informado para oportunidade perdida.',
                );
            }

            $createdAt = now();

            $terminalState = match ($stage->type) {
                'won' => [
                    'won_at' => $createdAt,
                    'lost_at' => null,
                    'loss_reason_id' => null,
                ],
                'lost' => [
                    'won_at' => null,
                    'lost_at' => $createdAt,
                    'loss_reason_id' => $lossReason?->id,
                ],
                default => [
                    'won_at' => null,
                    'lost_at' => null,
                    'loss_reason_id' => null,
                ],
            };

            $opportunity = Opportunity::create([
                ...$data,
                'probability' => $stage->probability,
                ...$terminalState,
            ]);

            OpportunityStageHistory::create([
                'pipeline_id' => $opportunity->pipeline_id,
                'opportunity_id' => $opportunity->id,
                'from_stage_id' => null,
                'to_stage_id' => $stage->id,
                'changed_by_user_id' => $createdBy?->id,
                'changed_at' => $createdAt,
                'notes' => 'Criação da oportunidade.',
            ]);

            $this->audit->record(
                'opportunity_created',
                $opportunity,
                null,
                $opportunity->attributesToArray(),
                $createdBy,
            );

            return $opportunity->refresh();
        });
    }
}
