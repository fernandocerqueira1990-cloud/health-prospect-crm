<?php

namespace App\Actions\Leads;

use App\Models\Lead;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class UpdateLeadAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    /** @param array<string, mixed> $data */
    public function execute(Lead $lead, array $data): Lead
    {
        return DB::transaction(function () use ($lead, $data): Lead {
            $lead = Lead::query()
                ->lockForUpdate()
                ->findOrFail($lead->getKey());

            $before = $lead->attributesToArray();

            $lead->update($data);
            $lead->refresh();

            $this->audit->record(
                'lead_updated',
                $lead,
                $before,
                $lead->attributesToArray(),
            );

            return $lead;
        });
    }
}
