<?php

namespace App\Actions\Tasks;

use App\Actions\Activities\CreateActivityAction;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompleteFollowUpAction
{
    public function __construct(
        private readonly CreateActivityAction $createActivity,
        private readonly UpdateTaskAction $updateTask,
    ) {}

    public function execute(
        Task $task,
        User $user,
        ?string $outcome = null,
    ): Task {
        return DB::transaction(function () use (
            $task,
            $user,
            $outcome,
        ): Task {
            if (! $task->is_follow_up) {
                throw ValidationException::withMessages([
                    'task' => 'A tarefa informada não é um follow-up.',
                ]);
            }

            if ($task->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'task' => 'Um follow-up cancelado não pode ser concluído.',
                ]);
            }

            if (
                $task->status === 'completed'
                || $task->completed_activity_id !== null
            ) {
                throw ValidationException::withMessages([
                    'task' => 'Este follow-up já foi concluído.',
                ]);
            }

            if (
                ! in_array(
                    $task->follow_up_channel,
                    Task::FOLLOW_UP_CHANNELS,
                    true,
                )
            ) {
                throw ValidationException::withMessages([
                    'follow_up_channel' => 'Canal de follow-up inválido.',
                ]);
            }

            $direction = in_array(
                $task->follow_up_channel,
                ['call', 'email', 'whatsapp'],
                true,
            )
                ? 'outbound'
                : null;

            $activity = $this->createActivity->execute([
                'type' => $task->follow_up_channel,
                'direction' => $direction,
                'subject' => $task->title,
                'description' => $task->description,
                'outcome' => $outcome,
                'company_id' => $task->company_id,
                'contact_id' => $task->contact_id,
                'lead_id' => $task->lead_id,
                'opportunity_id' => $task->opportunity_id,
                'assigned_user_id' => $task->assigned_user_id
                    ?? $user->id,
                'occurred_at' => now(),
                'duration_minutes' => null,
            ], $user);

            return $this->updateTask->execute($task, [
                'status' => 'completed',
                'completed_activity_id' => $activity->id,
            ]);
        });
    }
}
