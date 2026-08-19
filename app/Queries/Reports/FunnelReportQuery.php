<?php

namespace App\Queries\Reports;

use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\Stage;
use App\Queries\Reports\Concerns\AppliesReportPeriod;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class FunnelReportQuery
{
    use AppliesReportPeriod;

    /**
     * @param  array{date_from?: string|null, date_to?: string|null}  $filters
     * @return array<int, array{
     *     id: int|null,
     *     name: string,
     *     total: int,
     *     stages: array<int, array{id: int|null, name: string, position: int|null, active: bool|null, count: int, percentage: float}>,
     *     open: int,
     *     won: int,
     *     lost: int,
     *     open_rate: float,
     *     win_rate: float,
     *     loss_rate: float
     * }>
     */
    public function get(array $filters): array
    {
        $groups = $this->applyPeriod(Opportunity::query(), $filters)
            ->select(['pipeline_id', 'stage_id'])
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('SUM(CASE WHEN won_at IS NULL AND lost_at IS NULL THEN 1 ELSE 0 END) AS open')
            ->selectRaw('SUM(CASE WHEN won_at IS NOT NULL THEN 1 ELSE 0 END) AS won')
            ->selectRaw('SUM(CASE WHEN lost_at IS NOT NULL THEN 1 ELSE 0 END) AS lost')
            ->groupBy(['pipeline_id', 'stage_id'])
            ->get();

        if ($groups->isEmpty()) {
            return [];
        }

        $pipelineIds = $groups->pluck('pipeline_id')->filter()->unique()->values();
        $pipelines = Pipeline::query()
            ->whereKey($pipelineIds)
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->keyBy('id');
        $stages = Stage::query()
            ->whereIn('pipeline_id', $pipelineIds)
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->groupBy('pipeline_id')
            ->toBase();

        return $groups
            ->groupBy(fn (Opportunity $group): string => (string) ($group->pipeline_id ?? 'legacy'))
            ->map(fn (Collection $pipelineGroups): array => $this->pipelineData($pipelineGroups, $pipelines, $stages))
            ->sortBy([
                ['name', 'asc'],
                ['id', 'asc'],
            ], SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Opportunity>  $groups
     * @param  Collection<int, Pipeline>  $pipelines
     * @param  Collection<int|string, EloquentCollection<int, Stage>>  $stages
     * @return array{id: int|null, name: string, total: int, stages: array<int, array{id: int|null, name: string, position: int|null, active: bool|null, count: int, percentage: float}>, open: int, won: int, lost: int, open_rate: float, win_rate: float, loss_rate: float}
     */
    private function pipelineData(Collection $groups, Collection $pipelines, Collection $stages): array
    {
        $pipelineId = $groups->first()?->pipeline_id;
        $pipeline = $pipelineId === null ? null : $pipelines->firstWhere('id', $pipelineId);
        $total = (int) $groups->sum(fn (Opportunity $group): int => (int) $group->getAttribute('total'));
        $countsByStage = $groups->keyBy(fn (Opportunity $group): string => (string) ($group->stage_id ?? 'legacy'));
        $pipelineStages = $pipeline === null ? collect() : $stages->get($pipeline->id, collect());

        $stageData = $pipelineStages
            ->filter(function (Stage $stage) use ($countsByStage): bool {
                $count = (int) $countsByStage->get((string) $stage->id)?->getAttribute('total');

                return $stage->active || $count > 0;
            })
            ->map(function (Stage $stage) use ($countsByStage, $total): array {
                $count = (int) $countsByStage->get((string) $stage->id)?->getAttribute('total');

                return $this->stageData($stage->id, $stage->name, $stage->position, $stage->active, $count, $total);
            });

        $knownStageIds = $pipelineStages->pluck('id')->map(fn (int $id): string => (string) $id);
        $legacyCount = (int) $groups
            ->reject(fn (Opportunity $group): bool => $knownStageIds->contains((string) $group->stage_id))
            ->sum(fn (Opportunity $group): int => (int) $group->getAttribute('total'));

        if ($legacyCount > 0) {
            $stageData->push($this->stageData(null, 'Etapa não informada', null, null, $legacyCount, $total));
        }

        $open = (int) $groups->sum(fn (Opportunity $group): int => (int) $group->getAttribute('open'));
        $won = (int) $groups->sum(fn (Opportunity $group): int => (int) $group->getAttribute('won'));
        $lost = (int) $groups->sum(fn (Opportunity $group): int => (int) $group->getAttribute('lost'));

        return [
            'id' => $pipeline === null ? null : $pipeline->id,
            'name' => $pipeline === null ? 'Pipeline não informado' : $pipeline->name,
            'total' => $total,
            'stages' => $stageData->values()->all(),
            'open' => $open,
            'won' => $won,
            'lost' => $lost,
            'open_rate' => $this->percentage($open, $total),
            'win_rate' => $this->percentage($won, $total),
            'loss_rate' => $this->percentage($lost, $total),
        ];
    }

    /** @return array{id: int|null, name: string, position: int|null, active: bool|null, count: int, percentage: float} */
    private function stageData(?int $id, string $name, ?int $position, ?bool $active, int $count, int $total): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'position' => $position,
            'active' => $active,
            'count' => $count,
            'percentage' => $this->percentage($count, $total),
        ];
    }

    private function percentage(int $part, int $total): float
    {
        return $total === 0 ? 0.0 : round(($part / $total) * 100, 1);
    }
}
