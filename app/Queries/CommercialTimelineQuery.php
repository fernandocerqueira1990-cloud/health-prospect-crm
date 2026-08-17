<?php

namespace App\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class CommercialTimelineQuery
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, object>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $activities = $this->activitiesQuery();
        $tasks = $this->tasksQuery();

        $union = $activities->unionAll($tasks);

        $query = DB::query()
            ->fromSub($union, 'timeline');

        $search = trim((string) ($filters['q'] ?? ''));

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                foreach ([
                    'title',
                    'description',
                    'outcome',
                    'company_name',
                    'contact_name',
                    'lead_name',
                    'opportunity_title',
                    'assigned_user_name',
                ] as $column) {
                    $query->orWhere(
                        $column,
                        'ilike',
                        '%'.$search.'%',
                    );
                }
            });
        }

        foreach ([
            'event_type',
            'channel',
            'status',
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
                'event_at',
                '>=',
                $filters['date_from'],
            );
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate(
                'event_at',
                '<=',
                $filters['date_to'],
            );
        }

        $perPage = isset($filters['per_page'])
            ? (int) $filters['per_page']
            : 30;

        return $query
            ->orderByDesc('event_at')
            ->orderByDesc('source_id')
            ->paginate($perPage)
            ->withQueryString();
    }

    private function activitiesQuery(): Builder
    {
        return DB::table('activities as a')
            ->leftJoin('companies as c', 'c.id', '=', 'a.company_id')
            ->leftJoin('contacts as ct', 'ct.id', '=', 'a.contact_id')
            ->leftJoin('leads as l', 'l.id', '=', 'a.lead_id')
            ->leftJoin(
                'opportunities as o',
                'o.id',
                '=',
                'a.opportunity_id',
            )
            ->leftJoin(
                'users as u',
                'u.id',
                '=',
                'a.assigned_user_id',
            )
            ->whereNull('a.deleted_at')
            ->select([
                DB::raw("'activity'::varchar as event_type"),
                'a.id as source_id',
                'a.subject as title',
                'a.description',
                'a.outcome',
                'a.type as channel',
                DB::raw("'completed'::varchar as status"),
                DB::raw('NULL::varchar as priority'),
                'a.occurred_at as event_at',
                DB::raw('NULL::timestamp as due_at'),
                'a.company_id',
                'a.contact_id',
                'a.lead_id',
                'a.opportunity_id',
                'a.assigned_user_id',
                'a.created_by_user_id',
                DB::raw(
                    'COALESCE(c.trade_name, c.legal_name) as company_name'
                ),
                'ct.name as contact_name',
                DB::raw(
                    'COALESCE(l.name, l.company_name) as lead_name'
                ),
                'o.title as opportunity_title',
                'u.name as assigned_user_name',
            ]);
    }

    private function tasksQuery(): Builder
    {
        return DB::table('tasks as t')
            ->leftJoin('companies as c', 'c.id', '=', 't.company_id')
            ->leftJoin('contacts as ct', 'ct.id', '=', 't.contact_id')
            ->leftJoin('leads as l', 'l.id', '=', 't.lead_id')
            ->leftJoin(
                'opportunities as o',
                'o.id',
                '=',
                't.opportunity_id',
            )
            ->leftJoin(
                'users as u',
                'u.id',
                '=',
                't.assigned_user_id',
            )
            ->whereNull('t.deleted_at')
            ->where(function (Builder $query): void {
                $query
                    ->whereNotNull('t.company_id')
                    ->orWhereNotNull('t.contact_id')
                    ->orWhereNotNull('t.lead_id')
                    ->orWhereNotNull('t.opportunity_id');
            })
            ->where(function (Builder $query): void {
                $query
                    ->where('t.is_follow_up', false)
                    ->orWhereNull('t.completed_activity_id')
                    ->orWhere('t.status', '!=', 'completed');
            })
            ->select([
                DB::raw("
                    CASE
                        WHEN t.is_follow_up = true
                            THEN 'follow_up'
                        ELSE 'task'
                    END::varchar as event_type
                "),
                't.id as source_id',
                't.title',
                't.description',
                DB::raw('NULL::text as outcome'),
                't.follow_up_channel as channel',
                't.status',
                't.priority',
                DB::raw("
                    CASE
                        WHEN t.status = 'completed'
                            THEN t.completed_at
                        WHEN t.status = 'cancelled'
                            THEN t.cancelled_at
                        ELSE COALESCE(t.due_at, t.created_at)
                    END as event_at
                "),
                't.due_at',
                't.company_id',
                't.contact_id',
                't.lead_id',
                't.opportunity_id',
                't.assigned_user_id',
                't.created_by_user_id',
                DB::raw(
                    'COALESCE(c.trade_name, c.legal_name) as company_name'
                ),
                'ct.name as contact_name',
                DB::raw(
                    'COALESCE(l.name, l.company_name) as lead_name'
                ),
                'o.title as opportunity_title',
                'u.name as assigned_user_name',
            ]);
    }
}
