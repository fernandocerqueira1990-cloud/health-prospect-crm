<?php

namespace App\Queries\Reports\Concerns;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

trait AppliesReportPeriod
{
    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @param  array{date_from?: string|null, date_to?: string|null}  $filters
     * @return Builder<TModel>
     */
    private function applyPeriod(Builder $query, array $filters, string $column = 'created_at'): Builder
    {
        if (! empty($filters['date_from'])) {
            $query->where($column, '>=', CarbonImmutable::createFromFormat('Y-m-d', $filters['date_from'])->startOfDay());
        }

        if (! empty($filters['date_to'])) {
            $query->where($column, '<', CarbonImmutable::createFromFormat('Y-m-d', $filters['date_to'])->addDay()->startOfDay());
        }

        return $query;
    }
}
