<?php

namespace App\Actions\Tasks;

use App\Models\Task;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class CreateTaskAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    /** @param array<string, mixed> $data */
    public function execute(array $data, User $user): Task
    {
        return DB::transaction(function () use ($data, $user): Task {
            $data['created_by_user_id'] = $user->id;

            if (empty($data['assigned_user_id'])) {
                $data['assigned_user_id'] = $user->id;
            }

            $this->applyStateDates($data);

            $task = Task::create($data);

            $this->audit->record(
                'task_created',
                $task,
                after: $task->attributesToArray(),
            );

            return $task;
        });
    }

    /** @param array<string, mixed> $data */
    private function applyStateDates(array &$data): void
    {
        $status = $data['status'] ?? 'pending';

        $data['started_at'] = $status === 'in_progress'
            ? now()
            : null;

        $data['completed_at'] = $status === 'completed'
            ? now()
            : null;

        $data['cancelled_at'] = $status === 'cancelled'
            ? now()
            : null;
    }
}
