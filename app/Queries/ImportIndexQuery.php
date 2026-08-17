<?php

namespace App\Queries;

use App\Models\DataImport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ImportIndexQuery
{
    /** @return LengthAwarePaginator<int, DataImport> */
    public function paginate(): LengthAwarePaginator
    {
        return DataImport::query()->with('user:id,name')->latest('id')->paginate(15);
    }
}
