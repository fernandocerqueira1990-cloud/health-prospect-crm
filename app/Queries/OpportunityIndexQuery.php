<?php

namespace App\Queries;

use App\Models\Opportunity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class OpportunityIndexQuery
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Opportunity>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Opportunity::query()->with([
            'lead:id,name,company_name,email',
            'company:id,legal_name,trade_name',
            'contact:id,company_id,name',
            'assignedUser:id,name',
            'pipeline:id,name,slug',
            'stage:id,pipeline_id,name,slug,position,probability,type',
            'lossReason:id,name,slug',
        ])->withMax('stageHistories as last_stage_changed_at', 'changed_at');

        $search = trim((string) ($filters['q'] ?? ''));

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('title', 'ilike', '%'.$search.'%');

                $query->orWhereHas(
                    'company',
                    function (Builder $company) use ($search): void {
                        $company
                            ->where('legal_name', 'ilike', '%'.$search.'%')
                            ->orWhere('trade_name', 'ilike', '%'.$search.'%');
                    },
                );

                $query->orWhereHas(
                    'lead',
                    function (Builder $lead) use ($search): void {
                        $lead
                            ->where('name', 'ilike', '%'.$search.'%')
                            ->orWhere('company_name', 'ilike', '%'.$search.'%')
                            ->orWhere('email', 'ilike', '%'.$search.'%');
                    },
                );

                $query->orWhereHas(
                    'contact',
                    function (Builder $contact) use ($search): void {
                        $contact->where('name', 'ilike', '%'.$search.'%');
                    },
                );
            });
        }

        foreach ([
            'pipeline_id',
            'stage_id',
            'assigned_user_id',
            'company_id',
            'lead_id',
        ] as $field) {
            if (isset($filters[$field]) && $filters[$field] !== '') {
                $query->where($field, $filters[$field]);
            }
        }

        if (isset($filters['state']) && $filters['state'] !== '') {
            $query->whereHas(
                'stage',
                fn (Builder $stage) => $stage->where(
                    'type',
                    $filters['state'],
                ),
            );
        }

        if (! empty($filters['stagnant'])) {
            $cutoff = now()->subDays(
                max(1, (int) config('commercial.opportunity_stagnation_days', 14)),
            );

            $query
                ->whereHas('stage', fn (Builder $stage) => $stage->where('type', 'open'))
                ->where('created_at', '<=', $cutoff)
                ->whereDoesntHave(
                    'stageHistories',
                    fn (Builder $history) => $history->where('changed_at', '>', $cutoff),
                );
        }

        $perPage = isset($filters['per_page'])
            ? (int) $filters['per_page']
            : 15;

        return $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
