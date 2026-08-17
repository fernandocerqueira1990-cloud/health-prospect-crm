<?php

namespace App\Actions\Companies;

use App\Models\Company;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class UpdateCompanyAction
{
    public function __construct(
        private readonly EnsureCompanyAssigneeIsAllowedAction $ensureAssigneeIsAllowed,
        private readonly AuditService $audit,
    ) {}

    public function execute(Company $company, array $data): Company
    {
        return DB::transaction(function () use ($company, $data): Company {
            $company = Company::query()->lockForUpdate()->findOrFail($company->getKey());
            $assignedUserId = array_key_exists('assigned_user_id', $data)
                ? ($data['assigned_user_id'] !== null ? (int) $data['assigned_user_id'] : null)
                : $company->assigned_user_id;
            $this->ensureAssigneeIsAllowed->execute($assignedUserId, $company->assigned_user_id);

            $before = $company->attributesToArray();
            $company->update($data);
            $company->refresh();
            $this->audit->record('company_updated', $company, $before, $company->attributesToArray());

            return $company;
        });
    }
}
