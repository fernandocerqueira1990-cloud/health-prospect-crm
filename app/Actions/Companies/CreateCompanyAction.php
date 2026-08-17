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

    /** @param array<string, mixed> $data @param array<string, mixed>|null $auditAfter */
    public function execute(array $data, ?array $auditAfter = null): Company
    {
        return DB::transaction(function () use ($data, $auditAfter): Company {
            $this->ensureAssigneeIsAllowed->execute(
                isset($data['assigned_user_id']) ? (int) $data['assigned_user_id'] : null,
            );
            $company = Company::create($data);
            $this->audit->record('company_created', $company, after: $auditAfter ?? $company->attributesToArray());

            return $company;
        });
    }
}
