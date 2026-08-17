<?php

namespace App\Actions\Tasks;

use App\Models\Task;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class DeleteTaskAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function execute(Task $task): void
    {
        DB::transaction(function () use ($task): void {
            $before = $task->attributesToArray();

            $task->delete();

            $this->audit->record(
                'task_deleted',
                $task,
                before: $before,
            );
        });
    }
}
