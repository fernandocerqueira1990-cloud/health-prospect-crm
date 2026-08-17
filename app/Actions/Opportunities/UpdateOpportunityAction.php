<?php

namespace App\Actions\Opportunities;

use App\Models\Opportunity;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class UpdateOpportunityAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    /** @param array<string, mixed> $data */
    public function execute(
        Opportunity $opportunity,
        array $data,
    ): Opportunity {
        return DB::transaction(function () use ($opportunity, $data): Opportunity {
            $opportunity = Opportunity::query()
                ->lockForUpdate()
                ->findOrFail($opportunity->getKey());

            $before = $opportunity->attributesToArray();

            $opportunity->update($data);
            $opportunity->refresh();

            $this->audit->record(
                'opportunity_updated',
                $opportunity,
                $before,
                $opportunity->attributesToArray(),
            );

            return $opportunity;
        });
    }
}
