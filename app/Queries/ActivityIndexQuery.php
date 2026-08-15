<?php

namespace App\Queries;

use App\Models\Activity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ActivityIndexQuery
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Activity>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Activity::query()->with([
            'company:id,legal_name,trade_name',
            'contact:id,company_id,name',
            'lead:id,name,company_name',
            'opportunity:id,title',
            'assignedUser:id,name',
            'createdByUser:id,name',
        ]);

        $search = trim((string) ($filters['q'] ?? ''));

        if ($search !== '') {
            $query->where(
                function (Builder $query) use ($search): void {
                    foreach ([
                        'subject',
                        'description',
                        'outcome',
                    ] as $column) {
                        $query->orWhere(
                            $column,
                            'ilike',
                            '%'.$search.'%',
                        );
                    }

                    $query->orWhereHas(
                        'company',
                        function (Builder $company) use ($search): void {
                            $company
                                ->where(
                                    'legal_name',
                                    'ilike',
                                    '%'.$search.'%',
                                )
                                ->orWhere(
                                    'trade_name',
                                    'ilike',
                                    '%'.$search.'%',
                                );
                        },
                    );

                    $query->orWhereHas(
                        'contact',
                        function (Builder $contact) use ($search): void {
                            $contact->where(
                                'name',
                                'ilike',
                                '%'.$search.'%',
                            );
                        },
                    );

                    $query->orWhereHas(
                        'lead',
                        function (Builder $lead) use ($search): void {
                            $lead
                                ->where(
                                    'name',
                                    'ilike',
                                    '%'.$search.'%',
                                )
                                ->orWhere(
                                    'company_name',
                                    'ilike',
                                    '%'.$search.'%',
                                );
                        },
                    );

                    $query->orWhereHas(
                        'opportunity',
                        function (Builder $opportunity) use ($search): void {
                            $opportunity->where(
                                'title',
                                'ilike',
                                '%'.$search.'%',
                            );
                        },
                    );
                },
            );
        }

        foreach ([
            'type',
            'direction',
            'company_id',
            'contact_id',
            'lead_id',
            'opportunity_id',
            'assigned_user_id',
        ] as $field) {
            if (
                isset($filters[$field])
                && $filters[$field] !== ''
            ) {
                $query->where(
                    $field,
                    $filters[$field],
                );
            }
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate(
                'occurred_at',
                '>=',
                $filters['date_from'],
            );
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate(
                'occurred_at',
                '<=',
                $filters['date_to'],
            );
        }

        $perPage = isset($filters['per_page'])
            ? (int) $filters['per_page']
            : 15;

        return $query
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
