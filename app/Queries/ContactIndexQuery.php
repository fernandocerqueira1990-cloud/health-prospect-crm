<?php

namespace App\Queries;

use App\Models\Contact;
use App\Support\PhoneNormalizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ContactIndexQuery
{
    private const SORTABLE = ['name', 'job_title', 'department', 'created_at', 'updated_at'];

    /** @param array<string, mixed> $filters
     * @return LengthAwarePaginator<int, Contact>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Contact::query()->with('company:id,legal_name,trade_name,deleted_at');
        $search = trim((string) ($filters['search'] ?? ''));
        $phoneSearch = PhoneNormalizer::searchCandidate($search);
        if ($search !== '') {
            $query->where(function (Builder $query) use ($search, $phoneSearch): void {
                foreach (['name', 'job_title', 'department', 'email'] as $column) {
                    $query->orWhere($column, 'ilike', '%'.$search.'%');
                }
                $query->orWhere('phone', 'ilike', '%'.($phoneSearch ?? $search).'%')
                    ->orWhere('whatsapp', 'ilike', '%'.($phoneSearch ?? $search).'%');
                $query->orWhereHas('company', fn (Builder $company) => $company
                    ->where('legal_name', 'ilike', '%'.$search.'%')
                    ->orWhere('trade_name', 'ilike', '%'.$search.'%'));
            });
        }
        foreach (['name', 'job_title', 'department', 'email', 'phone'] as $field) {
            $value = trim((string) ($filters[$field] ?? ''));
            if ($value !== '') {
                $query->where($field, 'ilike', '%'.$value.'%');
            }
        }
        if (isset($filters['company'])) {
            $query->where('company_id', (int) $filters['company']);
        }
        foreach (['decision_role', 'influence_level'] as $field) {
            if (isset($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }
        foreach (['is_primary', 'active'] as $field) {
            if (isset($filters[$field])) {
                $query->where($field, filter_var($filters[$field], FILTER_VALIDATE_BOOL));
            }
        }
        $sort = in_array($filters['sort'] ?? null, self::SORTABLE, true) ? $filters['sort'] : 'created_at';
        $direction = ($filters['direction'] ?? null) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $direction)->paginate(15)->withQueryString();
    }
}
