<?php

namespace App\Actions\Tasks;

use App\Models\Task;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class UpdateTaskAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    /** @param array<string, mixed> $data */
    public function execute(Task $task, array $data): Task
    {
        return DB::transaction(function () use ($task, $data): Task {
            $before = $task->attributesToArray();

            $this->applyStateDates($task, $data);

            $task->update($data);
            $task->refresh();

            $this->audit->record(
                'task_updated',
                $task,
                before: $before,
                after: $task->attributesToArray(),
            );

            return $task;
        });
    }

    /** @param array<string, mixed> $data */
    private function applyStateDates(Task $task, array &$data): void
    {
        $status = $data['status'] ?? $task->status;

        if ($status === 'in_progress') {
            $data['started_at'] = $task->started_at ?? now();
            $data['completed_at'] = null;
            $data['cancelled_at'] = null;

            return;
        }

        if ($status === 'completed') {
            $data['completed_at'] = $task->completed_at ?? now();
            $data['cancelled_at'] = null;

            return;
        }

        if ($status === 'cancelled') {
            $data['cancelled_at'] = $task->cancelled_at ?? now();
            $data['completed_at'] = null;

            return;
        }

        $data['completed_at'] = null;
        $data['cancelled_at'] = null;
    }
}
