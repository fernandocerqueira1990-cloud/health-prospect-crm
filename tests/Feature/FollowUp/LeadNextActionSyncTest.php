<?php

namespace Tests\Feature\FollowUp;

use App\Actions\Tasks\CreateTaskAction;
use App\Actions\Tasks\DeleteTaskAction;
use App\Actions\Tasks\UpdateTaskAction;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadNextActionSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_tasks_keeps_earliest_open_due_date_as_lead_next_action(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->create([
            'next_action_at' => null,
        ]);
        $action = app(CreateTaskAction::class);

        $later = now()->addDays(5)->startOfMinute();
        $earlier = now()->addDays(2)->startOfMinute();

        $action->execute($this->taskData($lead, $user, $later), $user);

        $this->assertTrue(
            $lead->fresh()->next_action_at->equalTo($later),
        );

        $action->execute($this->taskData($lead, $user, $earlier), $user);

        $this->assertTrue(
            $lead->fresh()->next_action_at->equalTo($earlier),
        );
    }

    public function test_completing_current_next_task_advances_lead_to_next_open_task(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->create([
            'next_action_at' => null,
        ]);
        $create = app(CreateTaskAction::class);
        $update = app(UpdateTaskAction::class);

        $firstDueAt = now()->addDay()->startOfMinute();
        $secondDueAt = now()->addDays(3)->startOfMinute();

        $first = $create->execute(
            $this->taskData($lead, $user, $firstDueAt),
            $user,
        );
        $create->execute(
            $this->taskData($lead, $user, $secondDueAt),
            $user,
        );

        $update->execute($first, [
            'status' => 'completed',
        ]);

        $this->assertTrue(
            $lead->fresh()->next_action_at->equalTo($secondDueAt),
        );
    }

    public function test_moving_task_between_leads_resynchronizes_both_leads(): void
    {
        $user = User::factory()->create();
        $sourceLead = Lead::factory()->create([
            'next_action_at' => null,
        ]);
        $targetLead = Lead::factory()->create([
            'next_action_at' => null,
        ]);
        $create = app(CreateTaskAction::class);
        $update = app(UpdateTaskAction::class);

        $dueAt = now()->addDays(2)->startOfMinute();

        $task = $create->execute(
            $this->taskData($sourceLead, $user, $dueAt),
            $user,
        );

        $update->execute($task, [
            'lead_id' => $targetLead->id,
        ]);

        $this->assertNull($sourceLead->fresh()->next_action_at);
        $this->assertTrue(
            $targetLead->fresh()->next_action_at->equalTo($dueAt),
        );
    }

    public function test_deleting_current_next_task_recalculates_lead_next_action(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->create([
            'next_action_at' => null,
        ]);
        $create = app(CreateTaskAction::class);
        $delete = app(DeleteTaskAction::class);

        $firstDueAt = now()->addDay()->startOfMinute();
        $secondDueAt = now()->addDays(4)->startOfMinute();

        $first = $create->execute(
            $this->taskData($lead, $user, $firstDueAt),
            $user,
        );
        $create->execute(
            $this->taskData($lead, $user, $secondDueAt),
            $user,
        );

        $delete->execute($first);

        $this->assertTrue(
            $lead->fresh()->next_action_at->equalTo($secondDueAt),
        );
    }

    /** @return array<string, mixed> */
    private function taskData(
        Lead $lead,
        User $user,
        \DateTimeInterface $dueAt,
    ): array {
        return [
            'title' => 'Próxima ação comercial',
            'description' => 'Contato comercial planejado.',
            'status' => 'pending',
            'priority' => 'medium',
            'is_follow_up' => false,
            'follow_up_channel' => null,
            'lead_id' => $lead->id,
            'assigned_user_id' => $user->id,
            'due_at' => $dueAt,
        ];
    }
}
