<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Task;
use App\Models\User;
use App\Notifications\CommercialAlertNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class CommercialNotificationService
{
    public function generateFor(User $user): int
    {
        $created = 0;

        foreach ($this->taskAlerts($user) as $alert) {
            $created += $this->sendOnce($user, $alert);
        }

        foreach ($this->leadAlerts($user) as $alert) {
            $created += $this->sendOnce($user, $alert);
        }

        foreach ($this->opportunityAlerts($user) as $alert) {
            $created += $this->sendOnce($user, $alert);
        }

        return $created;
    }

    /** @return array<int, array<string, mixed>> */
    private function taskAlerts(User $user): array
    {
        $tasks = Task::query()
            ->where('assigned_user_id', $user->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereNotNull('due_at')
            ->where('due_at', '<=', now()->endOfDay())
            ->get();

        return $tasks->map(function (Task $task): array {
            $isOverdue = $task->due_at->isBefore(now()->startOfDay());
            $type = $isOverdue ? 'task_overdue' : 'follow_up_today';

            return [
                'key' => $type.':'.$task->id.':'.$task->due_at->format('Y-m-d'),
                'type' => $type,
                'title' => $isOverdue ? 'Tarefa comercial atrasada' : 'Follow-up para hoje',
                'message' => $task->title,
                'severity' => $isOverdue ? 'danger' : 'warning',
                'url' => route('tasks.show', $task),
                'subject_type' => Task::class,
                'subject_id' => $task->id,
            ];
        })->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function leadAlerts(User $user): array
    {
        $cutoff = now()->subDays(
            max(1, (int) config('commercial.lead_inactivity_days', 7)),
        );

        $leads = Lead::query()
            ->where('assigned_user_id', $user->id)
            ->whereNotIn('status', ['converted', 'disqualified'])
            ->where('created_at', '<=', $cutoff)
            ->where(function (Builder $query) use ($cutoff): void {
                $query->whereNull('last_interaction_at')
                    ->orWhere('last_interaction_at', '<=', $cutoff);
            })
            ->get();

        return $leads->map(function (Lead $lead) use ($cutoff): array {
            return [
                'key' => 'lead_inactive:'.$lead->id.':'.$cutoff->format('Y-m-d'),
                'type' => 'lead_inactive',
                'title' => 'Lead sem interação',
                'message' => $lead->name ?: $lead->company_name ?: 'Lead #'.$lead->id,
                'severity' => 'warning',
                'url' => route('leads.show', $lead),
                'subject_type' => Lead::class,
                'subject_id' => $lead->id,
            ];
        })->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function opportunityAlerts(User $user): array
    {
        $cutoff = now()->subDays(
            max(1, (int) config('commercial.opportunity_stagnation_days', 14)),
        );

        $opportunities = Opportunity::query()
            ->where('assigned_user_id', $user->id)
            ->where('created_at', '<=', $cutoff)
            ->whereHas('stage', fn (Builder $stage) => $stage->where('type', 'open'))
            ->whereDoesntHave(
                'stageHistories',
                fn (Builder $history) => $history->where('changed_at', '>', $cutoff),
            )
            ->get();

        return $opportunities->map(function (Opportunity $opportunity) use ($cutoff): array {
            return [
                'key' => 'opportunity_stagnant:'.$opportunity->id.':'.$cutoff->format('Y-m-d'),
                'type' => 'opportunity_stagnant',
                'title' => 'Oportunidade parada no pipeline',
                'message' => $opportunity->title,
                'severity' => 'warning',
                'url' => route('opportunities.show', $opportunity),
                'subject_type' => Opportunity::class,
                'subject_id' => $opportunity->id,
            ];
        })->all();
    }

    /** @param array<string, mixed> $payload */
    private function sendOnce(User $user, array $payload): int
    {
        $alreadyExists = $user->notifications()
            ->where('data->key', $payload['key'])
            ->exists();

        if ($alreadyExists) {
            return 0;
        }

        Notification::send($user, new CommercialAlertNotification($payload));

        return 1;
    }
}
