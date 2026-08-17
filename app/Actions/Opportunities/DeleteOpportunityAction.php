<?php

namespace App\Actions\Opportunities;

use App\Models\Opportunity;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class DeleteOpportunityAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function execute(Opportunity $opportunity): void
    {
        DB::transaction(function () use ($opportunity): void {
            $opportunity = Opportunity::query()
                ->lockForUpdate()
                ->findOrFail($opportunity->getKey());

            $before = $opportunity->attributesToArray();

            $opportunity->delete();

            $this->audit->record(
                'opportunity_deleted',
                $opportunity,
                $before,
                $opportunity->attributesToArray(),
            );
        });
    }
}
