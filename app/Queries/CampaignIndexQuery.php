<?php

namespace App\Queries;

use App\Models\Campaign;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class CampaignIndexQuery
{
    private const SORTABLE = ['name', 'status', 'start_date', 'end_date', 'budget', 'created_at'];

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Campaign>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Campaign::query()->with(['channel:id,name', 'owner:id,name']);
        $search = trim((string) ($filters['q'] ?? ''));

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                foreach (['name', 'description', 'utm_campaign', 'utm_source'] as $column) {
                    $query->orWhere($column, 'ilike', '%'.$search.'%');
                }
            });
        }

        foreach (['status', 'channel_id', 'owner_user_id'] as $field) {
            if (isset($filters[$field]) && $filters[$field] !== '') {
                $query->where($field, $filters[$field]);
            }
        }

        foreach ([
            'start_date_from' => ['start_date', '>='],
            'start_date_to' => ['start_date', '<='],
            'end_date_from' => ['end_date', '>='],
            'end_date_to' => ['end_date', '<='],
        ] as $filter => [$column, $operator]) {
            if (isset($filters[$filter])) {
                $query->whereDate($column, $operator, (string) $filters[$filter]);
            }
        }

        $sort = in_array($filters['sort'] ?? null, self::SORTABLE, true) ? $filters['sort'] : 'created_at';
        $direction = ($filters['direction'] ?? null) === 'asc' ? 'asc' : 'desc';

        return $query
            ->orderBy($sort, $direction)
            ->orderBy('id', $direction)
            ->paginate(15)
            ->withQueryString();
    }
}
