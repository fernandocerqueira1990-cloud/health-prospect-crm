<?php

namespace App\Queries\Reports;

use App\Models\Campaign;
use App\Models\Lead;
use App\Queries\Reports\Concerns\AppliesReportPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AcquisitionReportQuery
{
    use AppliesReportPeriod;

    /**
     * @param  array{date_from?: string|null, date_to?: string|null}  $filters
     * @return array{
     *     sources: array<int, array{name: string, count: int, percentage: float}>,
     *     channels: array<int, array{name: string, count: int, percentage: float}>,
     *     campaigns: array<int, array{id: int, name: string, channel: string|null, attributed_leads: int, leads_with_opportunity: int, opportunities: int, won_opportunities: int, lead_to_opportunity_conversion: float, opportunity_to_won_conversion: float}>
     * }
     */
    public function get(array $filters): array
    {
        $leads = $this->periodLeadIds($filters);
        $totalLeads = (clone $leads)->count();

        return [
            'sources' => $this->distribution($filters, $totalLeads, 'source'),
            'channels' => $this->distribution($filters, $totalLeads, 'channel'),
            'campaigns' => $this->campaigns($leads),
        ];
    }

    /**
     * @param  array{date_from?: string|null, date_to?: string|null}  $filters
     * @return array<int, array{name: string, count: int, percentage: float}>
     */
    private function distribution(array $filters, int $totalLeads, string $dimension): array
    {
        $isSource = $dimension === 'source';
        $catalogTable = $isSource ? 'lead_sources' : 'channels';
        $fallbackId = $isSource ? 'source_id' : 'channel_id';
        $snapshotColumn = $isSource ? 'first_touch.source' : 'first_touch.channel';
        $snapshotCatalog = $isSource ? 'snapshot_sources' : 'snapshot_channels';

        return $this->applyPeriod(Lead::query(), $filters, 'leads.created_at')
            ->leftJoin('lead_source_events as first_touch', 'first_touch.id', '=', 'leads.first_touch_source_event_id')
            ->leftJoin("{$catalogTable} as fallback_catalog", 'fallback_catalog.id', '=', "leads.{$fallbackId}")
            ->leftJoin("{$catalogTable} as {$snapshotCatalog}", function ($join) use ($snapshotCatalog, $snapshotColumn): void {
                $join->on("{$snapshotCatalog}.slug", '=', $snapshotColumn);
            })
            ->selectRaw("COALESCE({$snapshotCatalog}.name, NULLIF({$snapshotColumn}, ''), fallback_catalog.name, 'Não informado') AS label")
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('label')
            ->orderByDesc('total')
            ->orderBy('label')
            ->get()
            ->map(fn (Lead $row): array => [
                'name' => (string) $row->getAttribute('label'),
                'count' => (int) $row->getAttribute('total'),
                'percentage' => $this->percentage((int) $row->getAttribute('total'), $totalLeads),
            ])
            ->all();
    }

    /**
     * @param  Builder<Lead>  $periodLeadIds
     * @return array<int, array{id: int, name: string, channel: string|null, attributed_leads: int, leads_with_opportunity: int, opportunities: int, won_opportunities: int, lead_to_opportunity_conversion: float, opportunity_to_won_conversion: float}>
     */
    private function campaigns(Builder $periodLeadIds): array
    {
        $attributions = DB::table('lead_source_events')
            ->select(['campaign_id', 'lead_id'])
            ->whereNotNull('campaign_id')
            ->distinct();
        $opportunities = DB::table('opportunities')
            ->whereNull('deleted_at')
            ->selectRaw('lead_id, COUNT(*) AS opportunities, SUM(CASE WHEN won_at IS NOT NULL THEN 1 ELSE 0 END) AS won_opportunities')
            ->groupBy('lead_id');

        return Campaign::query()
            ->joinSub($attributions, 'attributions', 'attributions.campaign_id', '=', 'campaigns.id')
            ->joinSub($periodLeadIds, 'period_leads', 'period_leads.id', '=', 'attributions.lead_id')
            ->leftJoinSub($opportunities, 'opportunity_metrics', 'opportunity_metrics.lead_id', '=', 'period_leads.id')
            ->leftJoin('channels', 'channels.id', '=', 'campaigns.channel_id')
            ->select(['campaigns.id', 'campaigns.name', 'channels.name as channel'])
            ->selectRaw('COUNT(DISTINCT period_leads.id) AS attributed_leads')
            ->selectRaw('COUNT(DISTINCT CASE WHEN opportunity_metrics.opportunities > 0 THEN period_leads.id END) AS leads_with_opportunity')
            ->selectRaw('COALESCE(SUM(opportunity_metrics.opportunities), 0) AS opportunities')
            ->selectRaw('COALESCE(SUM(opportunity_metrics.won_opportunities), 0) AS won_opportunities')
            ->groupBy(['campaigns.id', 'campaigns.name', 'channels.name'])
            ->orderByDesc('attributed_leads')
            ->orderBy('campaigns.name')
            ->orderBy('campaigns.id')
            ->get()
            ->map(function (Campaign $row): array {
                $attributedLeads = (int) $row->getAttribute('attributed_leads');
                $leadsWithOpportunity = (int) $row->getAttribute('leads_with_opportunity');
                $opportunities = (int) $row->getAttribute('opportunities');
                $won = (int) $row->getAttribute('won_opportunities');

                return [
                    'id' => $row->id,
                    'name' => $row->name,
                    'channel' => $row->getAttribute('channel'),
                    'attributed_leads' => $attributedLeads,
                    'leads_with_opportunity' => $leadsWithOpportunity,
                    'opportunities' => $opportunities,
                    'won_opportunities' => $won,
                    'lead_to_opportunity_conversion' => $this->percentage($leadsWithOpportunity, $attributedLeads),
                    'opportunity_to_won_conversion' => $this->percentage($won, $opportunities),
                ];
            })
            ->all();
    }

    /** @param array{date_from?: string|null, date_to?: string|null} $filters @return Builder<Lead> */
    private function periodLeadIds(array $filters): Builder
    {
        return $this->applyPeriod(Lead::query(), $filters)->select('leads.id');
    }

    private function percentage(int $part, int $total): float
    {
        return $total === 0 ? 0.0 : round(($part / $total) * 100, 1);
    }
}
