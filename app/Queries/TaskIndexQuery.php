<?php

namespace App\Queries;

use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class TaskIndexQuery
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Task>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Task::query()->with([
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
                    $query
                        ->where(
                            'title',
                            'ilike',
                            '%'.$search.'%',
                        )
                        ->orWhere(
                            'description',
                            'ilike',
                            '%'.$search.'%',
                        );

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
            'status',
            'priority',
            'assigned_user_id',
            'company_id',
            'contact_id',
            'lead_id',
            'opportunity_id',
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

        if (! empty($filters['due_from'])) {
            $query->whereDate(
                'due_at',
                '>=',
                $filters['due_from'],
            );
        }

        if (! empty($filters['due_to'])) {
            $query->whereDate(
                'due_at',
                '<=',
                $filters['due_to'],
            );
        }

        $perPage = isset($filters['per_page'])
            ? (int) $filters['per_page']
            : 15;

        return $query
            ->orderByRaw(
                "CASE
                    WHEN status = 'in_progress' THEN 0
                    WHEN status = 'pending' THEN 1
                    WHEN status = 'completed' THEN 2
                    ELSE 3
                END",
            )
            ->orderByRaw(
                'CASE WHEN due_at IS NULL THEN 1 ELSE 0 END',
            )
            ->orderBy('due_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
