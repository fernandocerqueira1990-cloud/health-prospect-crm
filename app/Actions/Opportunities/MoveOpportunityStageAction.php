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

class MoveOpportunityStageAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function execute(
        Opportunity $opportunity,
        Stage $targetStage,
        ?User $changedBy = null,
        ?string $notes = null,
        ?LossReason $lossReason = null,
    ): Opportunity {
        return DB::transaction(function () use (
            $opportunity,
            $targetStage,
            $changedBy,
            $notes,
            $lossReason,
        ): Opportunity {
            $opportunity = Opportunity::query()
                ->lockForUpdate()
                ->findOrFail($opportunity->getKey());

            $targetStage = Stage::query()
                ->findOrFail($targetStage->getKey());

            if ($targetStage->pipeline_id !== $opportunity->pipeline_id) {
                throw new DomainException(
                    'O estágio de destino não pertence ao pipeline da oportunidade.',
                );
            }

            if (! $targetStage->active) {
                throw new DomainException(
                    'Não é permitido mover uma oportunidade para um estágio inativo.',
                );
            }

            if ($targetStage->id === $opportunity->stage_id) {
                return $opportunity;
            }

            if ($targetStage->type === 'lost') {
                if ($lossReason === null) {
                    throw new DomainException(
                        'O motivo da perda é obrigatório.',
                    );
                }

                $lossReason = LossReason::query()
                    ->findOrFail($lossReason->getKey());

                if (! $lossReason->active) {
                    throw new DomainException(
                        'O motivo da perda informado está inativo.',
                    );
                }
            } elseif ($lossReason !== null) {
                throw new DomainException(
                    'Motivo da perda só pode ser informado ao mover para Perdido.',
                );
            }

            $before = $opportunity->attributesToArray();
            $fromStageId = $opportunity->stage_id;
            $changedAt = now();

            $terminalState = match ($targetStage->type) {
                'won' => [
                    'won_at' => $changedAt,
                    'lost_at' => null,
                    'loss_reason_id' => null,
                ],
                'lost' => [
                    'won_at' => null,
                    'lost_at' => $changedAt,
                    'loss_reason_id' => $lossReason?->id,
                ],
                default => [
                    'won_at' => null,
                    'lost_at' => null,
                    'loss_reason_id' => null,
                ],
            };

            $opportunity->update([
                'stage_id' => $targetStage->id,
                'probability' => $targetStage->probability,
                ...$terminalState,
            ]);

            OpportunityStageHistory::create([
                'pipeline_id' => $opportunity->pipeline_id,
                'opportunity_id' => $opportunity->id,
                'from_stage_id' => $fromStageId,
                'to_stage_id' => $targetStage->id,
                'changed_by_user_id' => $changedBy?->id,
                'changed_at' => $changedAt,
                'notes' => $notes,
            ]);

            $opportunity->refresh();

            $this->audit->record(
                'opportunity_stage_changed',
                $opportunity,
                $before,
                $opportunity->attributesToArray(),
                $changedBy,
            );

            return $opportunity;
        });
    }
}
