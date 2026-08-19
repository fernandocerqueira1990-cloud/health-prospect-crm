<?php

namespace App\Queries\Reports;

use App\Models\Opportunity;
use App\Queries\Reports\Concerns\AppliesReportPeriod;

class ExecutiveCommercialQuery
{
    use AppliesReportPeriod;

    /**
     * @param  array{date_from?: string|null, date_to?: string|null}  $filters
     * @return array{
     *     open_pipeline: array<int, array{currency: string, amount: string}>,
     *     won_value: array<int, array{currency: string, amount: string}>,
     *     lost_value: array<int, array{currency: string, amount: string}>,
     *     average_won_ticket: array<int, array{currency: string, amount: string}>
     * }
     */
    public function get(array $filters): array
    {
        $rows = $this->applyPeriod(Opportunity::query(), $filters)
            ->groupBy('currency')
            ->orderBy('currency')
            ->select('currency')
            ->selectRaw('SUM(CASE WHEN won_at IS NULL AND lost_at IS NULL THEN COALESCE(amount, 0) ELSE 0 END) AS open_amount')
            ->selectRaw('SUM(CASE WHEN won_at IS NOT NULL THEN COALESCE(amount, 0) ELSE 0 END) AS won_amount')
            ->selectRaw('SUM(CASE WHEN lost_at IS NOT NULL THEN COALESCE(amount, 0) ELSE 0 END) AS lost_amount')
            ->selectRaw('SUM(CASE WHEN won_at IS NOT NULL THEN 1 ELSE 0 END) AS won_count')
            ->selectRaw('SUM(CASE WHEN won_at IS NULL AND lost_at IS NULL THEN 1 ELSE 0 END) AS open_count')
            ->selectRaw('SUM(CASE WHEN lost_at IS NOT NULL THEN 1 ELSE 0 END) AS lost_count')
            ->get();

        $financials = [];

        foreach ($rows as $row) {
            $currency = $this->normalizeCurrency($row->currency);
            $wonCount = (int) $row->getAttribute('won_count');

            $financials[$currency] ??= [
                'open_amount' => 0.0,
                'won_amount' => 0.0,
                'lost_amount' => 0.0,
                'open_count' => 0,
                'won_count' => 0,
                'lost_count' => 0,
            ];

            $financials[$currency]['open_amount'] += (float) $row->getAttribute('open_amount');
            $financials[$currency]['won_amount'] += (float) $row->getAttribute('won_amount');
            $financials[$currency]['lost_amount'] += (float) $row->getAttribute('lost_amount');
            $financials[$currency]['open_count'] += (int) $row->getAttribute('open_count');
            $financials[$currency]['won_count'] += $wonCount;
            $financials[$currency]['lost_count'] += (int) $row->getAttribute('lost_count');
        }

        ksort($financials);

        return [
            'open_pipeline' => $this->valuesFor($financials, 'open_amount', 'open_count'),
            'won_value' => $this->valuesFor($financials, 'won_amount', 'won_count'),
            'lost_value' => $this->valuesFor($financials, 'lost_amount', 'lost_count'),
            'average_won_ticket' => $this->averageWonTickets($financials),
        ];
    }

    /**
     * @param  array<string, array{open_amount: float, won_amount: float, lost_amount: float, open_count: int, won_count: int, lost_count: int}>  $financials
     * @param  'open_amount'|'won_amount'|'lost_amount'  $amountKey
     * @param  'open_count'|'won_count'|'lost_count'  $countKey
     * @return array<int, array{currency: string, amount: string}>
     */
    private function valuesFor(array $financials, string $amountKey, string $countKey): array
    {
        $values = [];

        foreach ($financials as $currency => $totals) {
            if ($totals[$countKey] > 0) {
                $values[] = ['currency' => $currency, 'amount' => number_format($totals[$amountKey], 2, '.', '')];
            }
        }

        return $values;
    }

    /**
     * @param  array<string, array{open_amount: float, won_amount: float, lost_amount: float, open_count: int, won_count: int, lost_count: int}>  $financials
     * @return array<int, array{currency: string, amount: string}>
     */
    private function averageWonTickets(array $financials): array
    {
        $values = [];

        foreach ($financials as $currency => $totals) {
            if ($totals['won_count'] > 0) {
                $values[] = [
                    'currency' => $currency,
                    'amount' => number_format($totals['won_amount'] / $totals['won_count'], 2, '.', ''),
                ];
            }
        }

        return $values;
    }

    private function normalizeCurrency(mixed $currency): string
    {
        $normalized = strtoupper(trim((string) $currency));

        return preg_match('/^[A-Z]{3}$/', $normalized) === 1 ? $normalized : 'SEM MOEDA';
    }
}
