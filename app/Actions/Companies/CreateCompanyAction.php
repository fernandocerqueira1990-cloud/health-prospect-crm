<?php

namespace App\Actions\Companies;

use App\Models\Company;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class CreateCompanyAction
{
    public function __construct(
        private readonly EnsureCompanyAssigneeIsAllowedAction $ensureAssigneeIsAllowed,
        private readonly AuditService $audit,
    ) {}

    public function execute(array $data): Company
    {
        return DB::transaction(function () use ($data): Company {
            $this->ensureAssigneeIsAllowed->execute(
                isset($data['assigned_user_id']) ? (int) $data['assigned_user_id'] : null,
            );
            $company = Company::create($data);
            $this->audit->record('company_created', $company, after: $company->attributesToArray());

            return $company;
        });
    }
}
