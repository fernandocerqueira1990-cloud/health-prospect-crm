<?php

namespace Tests\Feature\FollowUp;

use App\Models\Activity;
use App\Models\Company;
use App\Models\Task;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FollowUpStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_follow_up_can_be_stored(): void
    {
        $company = Company::factory()->create();

        $task = Task::factory()->create([
            'title' => 'Retornar contato com cliente',
            'is_follow_up' => true,
            'follow_up_channel' => 'whatsapp',
            'company_id' => $company->id,
            'due_at' => now()->addDay(),
        ]);

        $this->assertTrue($task->is_follow_up);
        $this->assertSame('whatsapp', $task->follow_up_channel);
        $this->assertSame($company->id, $task->company_id);
        $this->assertNotNull($task->due_at);
    }

    public function test_follow_up_requires_channel(): void
    {
        $this->expectException(QueryException::class);

        Task::factory()->create([
            'is_follow_up' => true,
            'follow_up_channel' => null,
            'company_id' => Company::factory(),
            'due_at' => now()->addDay(),
        ]);
    }

    public function test_follow_up_requires_due_date(): void
    {
        $this->expectException(QueryException::class);

        Task::factory()->create([
            'is_follow_up' => true,
            'follow_up_channel' => 'call',
            'company_id' => Company::factory(),
            'due_at' => null,
        ]);
    }

    public function test_follow_up_requires_commercial_entity(): void
    {
        $this->expectException(QueryException::class);

        Task::factory()->create([
            'is_follow_up' => true,
            'follow_up_channel' => 'email',
            'company_id' => null,
            'contact_id' => null,
            'lead_id' => null,
            'opportunity_id' => null,
            'due_at' => now()->addDay(),
        ]);
    }

    public function test_database_rejects_unsupported_follow_up_channel(): void
    {
        $this->expectException(QueryException::class);

        Task::factory()->create([
            'is_follow_up' => true,
            'follow_up_channel' => 'sms',
            'company_id' => Company::factory(),
            'due_at' => now()->addDay(),
        ]);
    }

    public function test_regular_task_cannot_have_follow_up_channel(): void
    {
        $this->expectException(QueryException::class);

        Task::factory()->create([
            'is_follow_up' => false,
            'follow_up_channel' => 'whatsapp',
        ]);
    }

    public function test_completed_follow_up_can_reference_activity(): void
    {
        $company = Company::factory()->create();

        $activity = Activity::factory()->create([
            'type' => 'call',
            'company_id' => $company->id,
            'lead_id' => null,
        ]);

        $task = Task::factory()->create([
            'is_follow_up' => true,
            'follow_up_channel' => 'call',
            'company_id' => $company->id,
            'due_at' => now(),
            'status' => 'completed',
            'completed_at' => now(),
            'cancelled_at' => null,
            'completed_activity_id' => $activity->id,
        ]);

        $this->assertSame(
            $activity->id,
            $task->completed_activity_id,
        );

        $this->assertTrue(
            $task->completedActivity->is($activity),
        );
    }

    public function test_open_follow_up_cannot_reference_completed_activity(): void
    {
        $company = Company::factory()->create();

        $activity = Activity::factory()->create([
            'company_id' => $company->id,
            'lead_id' => null,
        ]);

        $this->expectException(QueryException::class);

        Task::factory()->create([
            'is_follow_up' => true,
            'follow_up_channel' => 'meeting',
            'company_id' => $company->id,
            'due_at' => now()->addDay(),
            'status' => 'pending',
            'completed_activity_id' => $activity->id,
        ]);
    }

    public function test_regular_task_cannot_reference_completed_activity(): void
    {
        $company = Company::factory()->create();

        $activity = Activity::factory()->create([
            'company_id' => $company->id,
            'lead_id' => null,
        ]);

        $this->expectException(QueryException::class);

        Task::factory()->create([
            'is_follow_up' => false,
            'company_id' => $company->id,
            'completed_activity_id' => $activity->id,
        ]);
    }
}
