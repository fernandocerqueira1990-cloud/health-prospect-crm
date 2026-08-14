<?php

namespace App\Actions\Companies;

use App\Models\Company;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class DeleteCompanyAction
{
    public function __construct(private readonly AuditService $audit) {}

    public function execute(Company $company): void
    {
        DB::transaction(function () use ($company): void {
            $before = $company->attributesToArray();
            $company->delete();
            $this->audit->record('company_deleted', $company, $before, $company->attributesToArray());
        });
    }
}
