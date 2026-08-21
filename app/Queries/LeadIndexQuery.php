<?php

namespace App\Queries;

use App\Models\Lead;
use App\Support\PhoneNormalizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class LeadIndexQuery
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Lead>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Lead::query()->with([
            'company:id,legal_name,trade_name',
            'contact:id,company_id,name',
            'assignedUser:id,name',
            'source:id,name',
            'channel:id,name',
        ]);

        $search = trim((string) ($filters['q'] ?? ''));

        if ($search !== '') {
            $phoneCandidate = PhoneNormalizer::searchCandidate($search);

            $query->where(function (Builder $query) use ($search, $phoneCandidate): void {
                foreach ([
                    'name',
                    'company_name',
                    'job_title',
                    'email',
                    'phone',
                    'whatsapp',
                ] as $column) {
                    $query->orWhere($column, 'ilike', '%'.$search.'%');
                }

                if ($phoneCandidate !== null && $phoneCandidate !== $search) {
                    $query->orWhere('phone', 'ilike', '%'.$phoneCandidate.'%')
                        ->orWhere('whatsapp', 'ilike', '%'.$phoneCandidate.'%');
                }

                $query->orWhereHas('company', function (Builder $company) use ($search): void {
                    $company->where('legal_name', 'ilike', '%'.$search.'%')
                        ->orWhere('trade_name', 'ilike', '%'.$search.'%');
                });

                $query->orWhereHas('contact', function (Builder $contact) use ($search): void {
                    $contact->where('name', 'ilike', '%'.$search.'%');
                });
            });
        }

        foreach ([
            'status',
            'priority',
            'temperature',
            'source_id',
            'channel_id',
            'assigned_user_id',
            'company_id',
        ] as $field) {
            if (isset($filters[$field]) && $filters[$field] !== '') {
                $query->where($field, $filters[$field]);
            }
        }

        if (! empty($filters['inactive'])) {
            $cutoff = now()->subDays(max(1, (int) config('commercial.lead_inactivity_days', 7)));

            $query
                ->whereNotIn('status', ['converted', 'disqualified'])
                ->where('created_at', '<=', $cutoff)
                ->where(function (Builder $query) use ($cutoff): void {
                    $query->whereNull('last_interaction_at')
                        ->orWhere('last_interaction_at', '<=', $cutoff);
                });
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
