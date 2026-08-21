<?php

namespace Tests\Feature\Dashboard;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRbac;
use Tests\TestCase;

class CommercialQueueTest extends TestCase
{
    use InteractsWithRbac, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRbac();
    }

    public function test_dashboard_groups_current_users_open_tasks_by_due_state(): void
    {
        $admin = $this->admin();
        $otherUser = $this->userWithPermission('tasks.view');

        Task::factory()->create([
            'assigned_user_id' => $admin->id,
            'status' => 'pending',
            'due_at' => now()->subDay(),
        ]);
        Task::factory()->create([
            'assigned_user_id' => $admin->id,
            'status' => 'in_progress',
            'due_at' => now()->addHour(),
        ]);
        Task::factory()->create([
            'assigned_user_id' => $admin->id,
            'status' => 'pending',
            'due_at' => now()->addDays(2),
        ]);
        Task::factory()->create([
            'assigned_user_id' => $admin->id,
            'status' => 'completed',
            'completed_at' => now(),
            'due_at' => now()->subDays(2),
        ]);
        Task::factory()->create([
            'assigned_user_id' => $otherUser->id,
            'status' => 'pending',
            'due_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Minhas pendências comerciais')
            ->assertSee('Atrasadas')
            ->assertSee('Para hoje')
            ->assertSee('Próximas');

        $response->assertViewHas(
            'commercialQueue',
            fn (?array $queue): bool => $queue !== null
                && $queue['overdue'] === 1
                && $queue['today'] === 1
                && $queue['upcoming'] === 1
                && $queue['next_tasks']->count() === 3,
        );
    }

    public function test_dashboard_hides_commercial_queue_without_task_permission(): void
    {
        $user = $this->userWithPermission('dashboard.view');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()
            ->assertDontSee('Minhas pendências comerciais');

        $response->assertViewHas(
            'commercialQueue',
            fn ($queue): bool => $queue === null,
        );
    }
}
