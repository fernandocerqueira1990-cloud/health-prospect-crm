<?php

namespace Tests\Feature\Task;

use App\Models\Company;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
        ]);

        $this->admin = User::factory()->create();

        $this->admin->roles()->attach(
            Role::query()
                ->where('slug', 'admin')
                ->firstOrFail(),
        );
    }

    public function test_admin_can_open_tasks_index(): void
    {
        Task::factory()->create([
            'title' => 'Revisar proposta comercial',
        ]);

        $response = $this
            ->actingAs($this->admin)
            ->get(route('tasks.index'));

        $response
            ->assertOk()
            ->assertSee('Tarefas')
            ->assertSee('Revisar proposta comercial');
    }

    public function test_admin_can_create_task(): void
    {
        $company = Company::factory()->create();

        $response = $this
            ->actingAs($this->admin)
            ->post(route('tasks.store'), [
                'title' => 'Agendar demonstração comercial',
                'description' => 'Entrar em contato com o cliente.',
                'status' => 'pending',
                'priority' => 'high',
                'company_id' => $company->id,
                'assigned_user_id' => $this->admin->id,
                'due_at' => now()->addDay()->format('Y-m-d H:i:s'),
            ]);

        $task = Task::query()
            ->where('title', 'Agendar demonstração comercial')
            ->firstOrFail();

        $response->assertRedirect(
            route('tasks.show', $task),
        );

        $this->assertSame('pending', $task->status);
        $this->assertSame('high', $task->priority);
        $this->assertSame($company->id, $task->company_id);
        $this->assertSame(
            $this->admin->id,
            $task->assigned_user_id,
        );
        $this->assertSame(
            $this->admin->id,
            $task->created_by_user_id,
        );

        $this->assertNull($task->started_at);
        $this->assertNull($task->completed_at);
        $this->assertNull($task->cancelled_at);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'task_created',
            'auditable_id' => $task->id,
        ]);
    }

    public function test_task_can_move_to_in_progress(): void
    {
        $task = Task::factory()->create([
            'status' => 'pending',
        ]);

        $response = $this
            ->actingAs($this->admin)
            ->put(route('tasks.update', $task), [
                'title' => $task->title,
                'description' => $task->description,
                'status' => 'in_progress',
                'priority' => $task->priority,
                'assigned_user_id' => $this->admin->id,
                'due_at' => $task->due_at?->format('Y-m-d H:i:s'),
            ]);

        $response->assertRedirect(
            route('tasks.show', $task),
        );

        $task->refresh();

        $this->assertSame('in_progress', $task->status);
        $this->assertNotNull($task->started_at);
        $this->assertNull($task->completed_at);
        $this->assertNull($task->cancelled_at);
    }

    public function test_task_can_be_completed(): void
    {
        $task = Task::factory()->create([
            'status' => 'pending',
        ]);

        $this
            ->actingAs($this->admin)
            ->put(route('tasks.update', $task), [
                'title' => $task->title,
                'status' => 'in_progress',
                'priority' => $task->priority,
                'assigned_user_id' => $this->admin->id,
            ])
            ->assertRedirect(
                route('tasks.show', $task),
            );

        $task->refresh();

        $startedAt = $task->started_at;

        $this
            ->actingAs($this->admin)
            ->put(route('tasks.update', $task), [
                'title' => $task->title,
                'status' => 'completed',
                'priority' => $task->priority,
                'assigned_user_id' => $this->admin->id,
            ])
            ->assertRedirect(
                route('tasks.show', $task),
            );

        $task->refresh();

        $this->assertSame('completed', $task->status);
        $this->assertNotNull($task->completed_at);
        $this->assertNull($task->cancelled_at);

        $this->assertEquals(
            $startedAt?->timestamp,
            $task->started_at?->timestamp,
        );
    }

    public function test_completed_task_can_be_cancelled(): void
    {
        $task = Task::factory()->create([
            'status' => 'completed',
            'completed_at' => now(),
            'cancelled_at' => null,
        ]);

        $response = $this
            ->actingAs($this->admin)
            ->put(route('tasks.update', $task), [
                'title' => $task->title,
                'status' => 'cancelled',
                'priority' => $task->priority,
                'assigned_user_id' => $this->admin->id,
            ]);

        $response->assertRedirect(
            route('tasks.show', $task),
        );

        $task->refresh();

        $this->assertSame('cancelled', $task->status);
        $this->assertNull($task->completed_at);
        $this->assertNotNull($task->cancelled_at);
    }

    public function test_admin_can_open_task_show(): void
    {
        $task = Task::factory()->create([
            'title' => 'Contato com hospital',
            'priority' => 'urgent',
        ]);

        $response = $this
            ->actingAs($this->admin)
            ->get(route('tasks.show', $task));

        $response
            ->assertOk()
            ->assertSee('Contato com hospital')
            ->assertSee('Urgente')
            ->assertSee('Vínculos comerciais');
    }

    public function test_admin_can_soft_delete_task(): void
    {
        $task = Task::factory()->create();

        $response = $this
            ->actingAs($this->admin)
            ->delete(route('tasks.destroy', $task));

        $response->assertRedirect(
            route('tasks.index'),
        );

        $this->assertSoftDeleted('tasks', [
            'id' => $task->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'task_deleted',
            'auditable_id' => $task->id,
        ]);
    }

    public function test_readonly_user_cannot_create_task(): void
    {
        $readonly = User::factory()->create();

        $readonly->roles()->attach(
            Role::query()
                ->where('slug', 'readonly')
                ->firstOrFail(),
        );

        $response = $this
            ->actingAs($readonly)
            ->post(route('tasks.store'), [
                'title' => 'Tarefa não autorizada',
                'status' => 'pending',
                'priority' => 'medium',
            ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('tasks', [
            'title' => 'Tarefa não autorizada',
        ]);
    }
}
