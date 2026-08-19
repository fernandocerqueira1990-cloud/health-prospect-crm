<?php

namespace App\Queries\Reports;

use App\Models\Lead;
use App\Models\Opportunity;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class CommercialSummaryQuery
{
    /**
     * @param  array{date_from?: string|null, date_to?: string|null}  $filters
     * @return array{
     *     leads: int,
     *     opportunities: int,
     *     open_opportunities: int,
     *     won_opportunities: int,
     *     lost_opportunities: int,
     *     lead_to_opportunity_conversion: float,
     *     opportunity_to_won_conversion: float
     * }
     */
    public function get(array $filters): array
    {
        $leads = $this->applyPeriod(Lead::query(), $filters);
        $opportunities = $this->applyPeriod(Opportunity::query(), $filters);

        $leadCount = (clone $leads)->count();
        $opportunityCounts = (clone $opportunities)
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('SUM(CASE WHEN won_at IS NULL AND lost_at IS NULL THEN 1 ELSE 0 END) AS open')
            ->selectRaw('SUM(CASE WHEN won_at IS NOT NULL THEN 1 ELSE 0 END) AS won')
            ->selectRaw('SUM(CASE WHEN lost_at IS NOT NULL THEN 1 ELSE 0 END) AS lost')
            ->firstOrFail();

        $opportunityCount = (int) $opportunityCounts->getAttribute('total');
        $wonCount = (int) $opportunityCounts->getAttribute('won');
        $leadsWithOpportunity = (clone $opportunities)
            ->whereNotNull('lead_id')
            ->whereIn('lead_id', (clone $leads)->select('id'))
            ->distinct()
            ->count('lead_id');

        return [
            'leads' => $leadCount,
            'opportunities' => $opportunityCount,
            'open_opportunities' => (int) $opportunityCounts->getAttribute('open'),
            'won_opportunities' => $wonCount,
            'lost_opportunities' => (int) $opportunityCounts->getAttribute('lost'),
            'lead_to_opportunity_conversion' => $this->percentage($leadsWithOpportunity, $leadCount),
            'opportunity_to_won_conversion' => $this->percentage($wonCount, $opportunityCount),
        ];
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @param  array{date_from?: string|null, date_to?: string|null}  $filters
     * @return Builder<TModel>
     */
    private function applyPeriod(Builder $query, array $filters): Builder
    {
        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', CarbonImmutable::createFromFormat('Y-m-d', $filters['date_from'])->startOfDay());
        }

        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<', CarbonImmutable::createFromFormat('Y-m-d', $filters['date_to'])->addDay()->startOfDay());
        }

        return $query;
    }

    private function percentage(int $part, int $total): float
    {
        return $total === 0 ? 0.0 : round(($part / $total) * 100, 1);
    }
}
