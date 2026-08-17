<?php

namespace App\Actions\Contacts;

use App\Models\Company;
use Illuminate\Database\Eloquent\Collection;

class LockContactCompaniesAction
{
    /**
     * Companies are always locked in ascending ID order before any Contact lock.
     *
     * @param  list<int>  $companyIds
     * @return Collection<int, Company>
     */
    public function execute(array $companyIds): Collection
    {
        $companyIds = array_values(array_unique($companyIds));
        sort($companyIds, SORT_NUMERIC);

        return Company::withTrashed()
            ->whereKey($companyIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }
}
