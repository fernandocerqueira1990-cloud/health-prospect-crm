<?php

namespace App\Actions\Tasks;

use App\Models\Task;
use App\Services\AuditService;
use App\Services\LeadNextActionSyncService;
use Illuminate\Support\Facades\DB;

class DeleteTaskAction
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly LeadNextActionSyncService $leadNextActionSync,
    ) {}

    public function execute(Task $task): void
    {
        DB::transaction(function () use ($task): void {
            $before = $task->attributesToArray();
            $leadId = $task->lead_id;

            $task->delete();

            $this->leadNextActionSync->sync($leadId);

            $this->audit->record(
                'task_deleted',
                $task,
                before: $before,
            );
        });
    }
}
