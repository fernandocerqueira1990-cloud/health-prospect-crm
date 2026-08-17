<?php

namespace Tests\Feature\Task;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_valid_task(): void
    {
        $task = Task::factory()->create([
            'title' => 'Revisar proposta comercial',
            'status' => 'pending',
            'priority' => 'high',
        ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Revisar proposta comercial',
            'status' => 'pending',
            'priority' => 'high',
        ]);
    }

    public function test_task_can_exist_without_commercial_entity(): void
    {
        $task = Task::factory()->create([
            'company_id' => null,
            'contact_id' => null,
            'lead_id' => null,
            'opportunity_id' => null,
        ]);

        $this->assertNotNull($task->id);
    }

    public function test_database_rejects_unsupported_status(): void
    {
        $this->expectException(QueryException::class);

        Task::factory()->create([
            'status' => 'invalid',
        ]);
    }

    public function test_database_rejects_unsupported_priority(): void
    {
        $this->expectException(QueryException::class);

        Task::factory()->create([
            'priority' => 'critical',
        ]);
    }

    public function test_completed_task_requires_completed_at(): void
    {
        $this->expectException(QueryException::class);

        Task::factory()->create([
            'status' => 'completed',
            'completed_at' => null,
        ]);
    }

    public function test_cancelled_task_requires_cancelled_at(): void
    {
        $this->expectException(QueryException::class);

        Task::factory()->create([
            'status' => 'cancelled',
            'cancelled_at' => null,
        ]);
    }

    public function test_pending_task_cannot_have_terminal_dates(): void
    {
        $this->expectException(QueryException::class);

        Task::factory()->create([
            'status' => 'pending',
            'completed_at' => now(),
        ]);
    }

    public function test_task_relationships_resolve_related_models(): void
    {
        $company = Company::factory()->create();

        $contact = Contact::factory()->create([
            'company_id' => $company->id,
        ]);

        $lead = Lead::factory()->create([
            'company_id' => $company->id,
            'contact_id' => $contact->id,
        ]);

        $opportunity = Opportunity::factory()->create([
            'company_id' => $company->id,
            'contact_id' => $contact->id,
            'lead_id' => $lead->id,
        ]);

        $assignedUser = User::factory()->create();
        $createdByUser = User::factory()->create();

        $task = Task::factory()->create([
            'company_id' => $company->id,
            'contact_id' => $contact->id,
            'lead_id' => $lead->id,
            'opportunity_id' => $opportunity->id,
            'assigned_user_id' => $assignedUser->id,
            'created_by_user_id' => $createdByUser->id,
        ]);

        $this->assertTrue($task->company->is($company));
        $this->assertTrue($task->contact->is($contact));
        $this->assertTrue($task->lead->is($lead));
        $this->assertTrue($task->opportunity->is($opportunity));
        $this->assertTrue($task->assignedUser->is($assignedUser));
        $this->assertTrue($task->createdByUser->is($createdByUser));
    }

    public function test_completed_task_can_be_stored(): void
    {
        $task = Task::factory()->create([
            'status' => 'completed',
            'completed_at' => now(),
            'cancelled_at' => null,
        ]);

        $this->assertNotNull($task->completed_at);
        $this->assertNull($task->cancelled_at);
    }

    public function test_cancelled_task_can_be_stored(): void
    {
        $task = Task::factory()->create([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'completed_at' => null,
        ]);

        $this->assertNotNull($task->cancelled_at);
        $this->assertNull($task->completed_at);
    }

    public function test_related_models_expose_task_relations(): void
    {
        $company = Company::factory()->create();

        $contact = Contact::factory()->create([
            'company_id' => $company->id,
        ]);

        $lead = Lead::factory()->create([
            'company_id' => $company->id,
            'contact_id' => $contact->id,
        ]);

        $opportunity = Opportunity::factory()->create([
            'company_id' => $company->id,
            'contact_id' => $contact->id,
            'lead_id' => $lead->id,
        ]);

        $assignedUser = User::factory()->create();
        $createdByUser = User::factory()->create();

        $task = Task::factory()->create([
            'company_id' => $company->id,
            'contact_id' => $contact->id,
            'lead_id' => $lead->id,
            'opportunity_id' => $opportunity->id,
            'assigned_user_id' => $assignedUser->id,
            'created_by_user_id' => $createdByUser->id,
        ]);

        $this->assertTrue(
            $company->tasks()->whereKey($task->id)->exists(),
        );

        $this->assertTrue(
            $contact->tasks()->whereKey($task->id)->exists(),
        );

        $this->assertTrue(
            $lead->tasks()->whereKey($task->id)->exists(),
        );

        $this->assertTrue(
            $opportunity->tasks()->whereKey($task->id)->exists(),
        );

        $this->assertTrue(
            $assignedUser->assignedTasks()
                ->whereKey($task->id)
                ->exists(),
        );

        $this->assertTrue(
            $createdByUser->createdTasks()
                ->whereKey($task->id)
                ->exists(),
        );
    }

    public function test_task_uses_soft_delete(): void
    {
        $task = Task::factory()->create();

        $task->delete();

        $this->assertSoftDeleted('tasks', [
            'id' => $task->id,
        ]);

        $this->assertNotNull(
            Task::withTrashed()->find($task->id),
        );
    }
}
