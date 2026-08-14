<?php

namespace App\Queries;

use App\Models\Company;
use App\Support\TaxIdNormalizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class CompanyIndexQuery
{
    private const SORTABLE = ['legal_name', 'trade_name', 'city', 'state', 'priority', 'created_at', 'updated_at'];

    /** @param array<string, mixed> $filters
     * @return LengthAwarePaginator<int, Company>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Company::query()->with('assignedUser:id,name');
        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $brazilianTaxId = TaxIdNormalizer::brazilianSearchCandidate($search);
            $query->where(function (Builder $query) use ($search, $brazilianTaxId): void {
                foreach (['legal_name', 'trade_name', 'email', 'phone', 'city', 'district'] as $column) {
                    $query->orWhere($column, 'ilike', '%'.$search.'%');
                }
                $query->orWhere('tax_id', 'ilike', '%'.$search.'%');

                if ($brazilianTaxId !== null && $brazilianTaxId !== $search) {
                    $query->orWhere('tax_id', 'ilike', '%'.$brazilianTaxId.'%');
                }
            });
        }

        foreach (['legal_name', 'trade_name', 'segment', 'category', 'city', 'state', 'district'] as $field) {
            $value = trim((string) ($filters[$field] ?? ''));
            if ($value !== '') {
                $query->where($field, 'ilike', '%'.$value.'%');
            }
        }

        if (($taxId = trim((string) ($filters['tax_id'] ?? ''))) !== '') {
            $brazilianTaxId = TaxIdNormalizer::brazilianSearchCandidate($taxId);
            $query->where(function (Builder $query) use ($taxId, $brazilianTaxId): void {
                $query->where('tax_id', 'ilike', '%'.$taxId.'%');

                if ($brazilianTaxId !== null && $brazilianTaxId !== $taxId) {
                    $query->orWhere('tax_id', 'ilike', '%'.$brazilianTaxId.'%');
                }
            });
        }

        if (isset($filters['assigned_user'])) {
            $query->where('assigned_user_id', (int) $filters['assigned_user']);
        }

        if (in_array($filters['priority'] ?? null, Company::PRIORITIES, true)) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['created_from'])) {
            $query->whereDate('created_at', '>=', (string) $filters['created_from']);
        }

        if (isset($filters['created_to'])) {
            $query->whereDate('created_at', '<=', (string) $filters['created_to']);
        }

        $sort = in_array($filters['sort'] ?? null, self::SORTABLE, true) ? $filters['sort'] : 'created_at';
        $direction = ($filters['direction'] ?? null) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $direction)->paginate(15)->withQueryString();
    }
}
