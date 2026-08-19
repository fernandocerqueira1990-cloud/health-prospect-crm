<?php

namespace App\Queries\Reports;

use App\Models\Opportunity;
use App\Models\OpportunityStageHistory;
use App\Queries\Reports\Concerns\AppliesReportPeriod;

class PipelineStageTimeQuery
{
    use AppliesReportPeriod;

    /**
     * @param  array{date_from?: string|null, date_to?: string|null}  $filters
     * @return array<int, array{
     *     pipeline_id: int,
     *     pipeline_name: string,
     *     total_open: int,
     *     stages: array<int, array{
     *         stage_id: int,
     *         stage_name: string,
     *         stage_position: int,
     *         opportunities: int,
     *         average_hours: float,
     *         max_hours: float
     *     }>
     * }>
     */
    public function get(array $filters): array
    {
        $query = Opportunity::query()
            ->whereNull('won_at')
            ->whereNull('lost_at')
            ->whereNotNull('pipeline_id')
            ->whereNotNull('stage_id')
            ->with([
                'pipeline:id,name',
                'stage:id,pipeline_id,name,position,active',
                'stageHistories:id,opportunity_id,to_stage_id,changed_at',
            ]);

        $opportunities = $this->applyPeriod($query, $filters)->get();
        $now = now();

        /** @var array<string, array{
         *     pipeline_id: int,
         *     pipeline_name: string,
         *     stage_id: int,
         *     stage_name: string,
         *     stage_position: int,
         *     opportunities: int,
         *     total_seconds: int,
         *     max_seconds: int
         * }> $buckets
         */
        $buckets = [];

        foreach ($opportunities as $opportunity) {
            if ($opportunity->pipeline === null || $opportunity->stage === null) {
                continue;
            }

            $lastEntry = $opportunity->stageHistories
                ->where('to_stage_id', $opportunity->stage_id)
                ->last();

            $enteredAt = $lastEntry instanceof OpportunityStageHistory
                ? $lastEntry->changed_at
                : $opportunity->created_at;

            if ($enteredAt === null) {
                continue;
            }

            $seconds = max(0, (int) $enteredAt->diffInSeconds($now));

            $key = $opportunity->pipeline_id.'-'.$opportunity->stage_id;

            if (! isset($buckets[$key])) {
                $buckets[$key] = [
                    'pipeline_id' => (int) $opportunity->pipeline_id,
                    'pipeline_name' => $opportunity->pipeline->name,
                    'stage_id' => (int) $opportunity->stage_id,
                    'stage_name' => $opportunity->stage->name,
                    'stage_position' => (int) $opportunity->stage->position,
                    'opportunities' => 0,
                    'total_seconds' => 0,
                    'max_seconds' => 0,
                ];
            }

            $buckets[$key]['opportunities']++;
            $buckets[$key]['total_seconds'] += $seconds;
            $buckets[$key]['max_seconds'] = max(
                $buckets[$key]['max_seconds'],
                $seconds,
            );
        }

        /** @var array<int, array{
         *     pipeline_id: int,
         *     pipeline_name: string,
         *     total_open: int,
         *     stages: array<int, array{
         *         stage_id: int,
         *         stage_name: string,
         *         stage_position: int,
         *         opportunities: int,
         *         average_hours: float,
         *         max_hours: float
         *     }>
         * }> $pipelines
         */
        $pipelines = [];

        foreach ($buckets as $bucket) {
            $pipelineId = $bucket['pipeline_id'];

            if (! isset($pipelines[$pipelineId])) {
                $pipelines[$pipelineId] = [
                    'pipeline_id' => $pipelineId,
                    'pipeline_name' => $bucket['pipeline_name'],
                    'total_open' => 0,
                    'stages' => [],
                ];
            }

            $pipelines[$pipelineId]['total_open'] += $bucket['opportunities'];

            $pipelines[$pipelineId]['stages'][] = [
                'stage_id' => $bucket['stage_id'],
                'stage_name' => $bucket['stage_name'],
                'stage_position' => $bucket['stage_position'],
                'opportunities' => $bucket['opportunities'],
                'average_hours' => round(
                    ($bucket['total_seconds'] / $bucket['opportunities']) / 3600,
                    1,
                ),
                'max_hours' => round($bucket['max_seconds'] / 3600, 1),
            ];
        }

        foreach ($pipelines as &$pipeline) {
            usort(
                $pipeline['stages'],
                fn (array $a, array $b): int => [
                    $a['stage_position'],
                    $a['stage_name'],
                ] <=> [
                    $b['stage_position'],
                    $b['stage_name'],
                ],
            );
        }

        unset($pipeline);

        usort(
            $pipelines,
            fn (array $a, array $b): int => $a['pipeline_name'] <=> $b['pipeline_name'],
        );

        return $pipelines;
    }
}
