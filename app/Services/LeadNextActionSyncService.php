<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Task;

class LeadNextActionSyncService
{
    public function sync(?int $leadId): void
    {
        if ($leadId === null) {
            return;
        }

        $lead = Lead::query()->find($leadId);

        if ($lead === null) {
            return;
        }

        $nextActionAt = Task::query()
            ->where('lead_id', $lead->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereNotNull('due_at')
            ->orderBy('due_at')
            ->value('due_at');

        $lead->forceFill([
            'next_action_at' => $nextActionAt,
        ])->save();
    }

    public function syncMany(?int ...$leadIds): void
    {
        collect($leadIds)
            ->filter(static fn (?int $leadId): bool => $leadId !== null)
            ->unique()
            ->each(function (int $leadId): void {
                $this->sync($leadId);
            });
    }
}
