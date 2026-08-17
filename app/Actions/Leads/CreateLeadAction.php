<?php

namespace App\Actions\Leads;

use App\Models\Lead;
use App\Models\LeadSourceEvent;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class CreateLeadAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    /** @param array<string, mixed> $data @param array<string, mixed>|null $auditAfter */
    public function execute(array $data, ?array $auditAfter = null): Lead
    {
        return DB::transaction(function () use ($data, $auditAfter): Lead {
            $lead = Lead::create($data);

            $lead->loadMissing([
                'source:id,name,slug',
                'channel:id,name,slug',
            ]);

            $event = LeadSourceEvent::create([
                'lead_id' => $lead->id,
                'event_type' => 'lead_created',
                'source' => $lead->source?->slug,
                'channel' => $lead->channel?->slug,
                'occurred_at' => now(),
                'metadata' => [
                    'origin' => 'crm',
                    'action' => 'lead_created',
                ],
            ]);

            $lead->update([
                'first_touch_source_event_id' => $event->id,
                'last_touch_source_event_id' => $event->id,
            ]);

            $lead->refresh();

            $this->audit->record(
                'lead_created',
                $lead,
                after: $auditAfter ?? $lead->attributesToArray(),
            );

            return $lead;
        });
    }
}
