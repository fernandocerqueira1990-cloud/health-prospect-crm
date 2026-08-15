<?php

namespace Tests\Feature\FollowUp;

use App\Actions\Tasks\CompleteFollowUpAction;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CompleteFollowUpActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_completing_follow_up_creates_activity(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();

        $task = Task::factory()->create([
            'title' => 'Retornar contato pelo WhatsApp',
            'description' => 'Apresentar proposta comercial.',
            'status' => 'pending',
            'is_follow_up' => true,
            'follow_up_channel' => 'whatsapp',
            'company_id' => $company->id,
            'assigned_user_id' => $user->id,
            'due_at' => now()->addDay(),
        ]);

        $this->actingAs($user);

        $task = app(CompleteFollowUpAction::class)->execute(
            $task,
            $user,
            'Cliente solicitou uma demonstração.',
        );

        $this->assertSame('completed', $task->status);
        $this->assertNotNull($task->completed_at);
        $this->assertNotNull($task->completed_activity_id);

        $activity = $task->completedActivity;

        $this->assertNotNull($activity);
        $this->assertSame('whatsapp', $activity->type);
        $this->assertSame('outbound', $activity->direction);
        $this->assertSame($task->title, $activity->subject);
        $this->assertSame($company->id, $activity->company_id);
        $this->assertSame($user->id, $activity->assigned_user_id);
        $this->assertSame($user->id, $activity->created_by_user_id);
        $this->assertSame(
            'Cliente solicitou uma demonstração.',
            $activity->outcome,
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'activity_created',
            'auditable_id' => $activity->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'task_updated',
            'auditable_id' => $task->id,
        ]);
    }

    public function test_regular_task_cannot_be_completed_as_follow_up(): void
    {
        $user = User::factory()->create();

        $task = Task::factory()->create([
            'is_follow_up' => false,
        ]);

        $this->actingAs($user);

        $this->expectException(ValidationException::class);

        app(CompleteFollowUpAction::class)->execute(
            $task,
            $user,
        );
    }

    public function test_cancelled_follow_up_cannot_be_completed(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();

        $task = Task::factory()->create([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'is_follow_up' => true,
            'follow_up_channel' => 'call',
            'company_id' => $company->id,
            'due_at' => now(),
        ]);

        $this->actingAs($user);

        $this->expectException(ValidationException::class);

        app(CompleteFollowUpAction::class)->execute(
            $task,
            $user,
        );
    }

    public function test_follow_up_cannot_be_completed_twice(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();

        $task = Task::factory()->create([
            'status' => 'pending',
            'is_follow_up' => true,
            'follow_up_channel' => 'email',
            'company_id' => $company->id,
            'due_at' => now(),
        ]);

        $this->actingAs($user);

        $action = app(CompleteFollowUpAction::class);

        $task = $action->execute(
            $task,
            $user,
            'Contato realizado.',
        );

        $activityId = $task->completed_activity_id;

        try {
            $action->execute(
                $task,
                $user,
                'Tentativa duplicada.',
            );

            $this->fail(
                'Era esperado impedir a segunda conclusão.',
            );
        } catch (ValidationException) {
            $task->refresh();

            $this->assertSame(
                $activityId,
                $task->completed_activity_id,
            );

            $this->assertSame(
                1,
                Activity::query()
                    ->where('subject', $task->title)
                    ->count(),
            );
        }
    }
}
