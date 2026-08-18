<?php

namespace App\Queries;

use App\Models\Campaign;
use App\Models\Lead;
use App\Models\Opportunity;
use Illuminate\Database\Eloquent\Builder;

class CampaignMetricsQuery
{
    /**
     * @return array{
     *     attributed_leads: int,
     *     leads_with_opportunity: int,
     *     opportunities: int,
     *     open_opportunities: int,
     *     won_opportunities: int,
     *     lost_opportunities: int,
     *     lead_to_opportunity_conversion: float,
     *     opportunity_to_won_conversion: float,
     *     open_values: array<int, array{currency: string, amount: string}>,
     *     won_values: array<int, array{currency: string, amount: string}>
     * }
     */
    public function get(Campaign $campaign): array
    {
        $attributedLeadIds = $this->attributedLeadIds($campaign);
        $attributedLeads = (clone $attributedLeadIds)->count();

        $opportunityCounts = Opportunity::query()
            ->whereIn('lead_id', clone $attributedLeadIds)
            ->selectRaw('COUNT(*) AS opportunities')
            ->selectRaw('COUNT(DISTINCT lead_id) AS leads_with_opportunity')
            ->selectRaw('SUM(CASE WHEN won_at IS NULL AND lost_at IS NULL THEN 1 ELSE 0 END) AS open_opportunities')
            ->selectRaw('SUM(CASE WHEN won_at IS NOT NULL THEN 1 ELSE 0 END) AS won_opportunities')
            ->selectRaw('SUM(CASE WHEN lost_at IS NOT NULL THEN 1 ELSE 0 END) AS lost_opportunities')
            ->firstOrFail();

        $opportunities = (int) $opportunityCounts->getAttribute('opportunities');
        $leadsWithOpportunity = (int) $opportunityCounts->getAttribute('leads_with_opportunity');

        $financials = Opportunity::query()
            ->whereIn('lead_id', clone $attributedLeadIds)
            ->groupBy('currency')
            ->orderBy('currency')
            ->select('currency')
            ->selectRaw('SUM(CASE WHEN won_at IS NULL AND lost_at IS NULL THEN COALESCE(amount, 0) ELSE 0 END) AS open_amount')
            ->selectRaw('SUM(CASE WHEN won_at IS NOT NULL THEN COALESCE(amount, 0) ELSE 0 END) AS won_amount')
            ->get();

        return [
            'attributed_leads' => $attributedLeads,
            'leads_with_opportunity' => $leadsWithOpportunity,
            'opportunities' => $opportunities,
            'open_opportunities' => (int) $opportunityCounts->getAttribute('open_opportunities'),
            'won_opportunities' => (int) $opportunityCounts->getAttribute('won_opportunities'),
            'lost_opportunities' => (int) $opportunityCounts->getAttribute('lost_opportunities'),
            'lead_to_opportunity_conversion' => $this->percentage($leadsWithOpportunity, $attributedLeads),
            'opportunity_to_won_conversion' => $this->percentage(
                (int) $opportunityCounts->getAttribute('won_opportunities'),
                $opportunities,
            ),
            'open_values' => $financials
                ->filter(fn (Opportunity $row): bool => (float) $row->getAttribute('open_amount') !== 0.0)
                ->map(fn (Opportunity $row): array => [
                    'currency' => (string) $row->currency,
                    'amount' => (string) $row->getAttribute('open_amount'),
                ])->values()->all(),
            'won_values' => $financials
                ->filter(fn (Opportunity $row): bool => (float) $row->getAttribute('won_amount') !== 0.0)
                ->map(fn (Opportunity $row): array => [
                    'currency' => (string) $row->currency,
                    'amount' => (string) $row->getAttribute('won_amount'),
                ])->values()->all(),
        ];
    }

    /** @return Builder<Lead> */
    private function attributedLeadIds(Campaign $campaign): Builder
    {
        return Lead::query()
            ->select('leads.id')
            ->whereHas(
                'sourceEvents',
                fn (Builder $events) => $events->where('campaign_id', $campaign->getKey()),
            );
    }

    private function percentage(int $part, int $total): float
    {
        return $total === 0 ? 0.0 : round(($part / $total) * 100, 1);
    }
}
