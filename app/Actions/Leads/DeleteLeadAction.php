<?php

namespace App\Actions\Leads;

use App\Models\Lead;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class DeleteLeadAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function execute(Lead $lead): void
    {
        DB::transaction(function () use ($lead): void {
            $lead = Lead::query()
                ->lockForUpdate()
                ->findOrFail($lead->getKey());

            $before = $lead->attributesToArray();

            $lead->delete();

            $this->audit->record(
                'lead_deleted',
                $lead,
                $before,
                $lead->attributesToArray(),
            );
        });
    }
}
