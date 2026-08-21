<?php

namespace Tests\Feature\Notifications;

use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use App\Services\CommercialNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommercialNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_creates_alerts_only_for_assigned_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Task::factory()->create([
            'assigned_user_id' => $user->id,
            'status' => 'pending',
            'due_at' => now()->subDay(),
        ]);

        Task::factory()->create([
            'assigned_user_id' => $otherUser->id,
            'status' => 'pending',
            'due_at' => now()->subDay(),
        ]);

        $created = app(CommercialNotificationService::class)->generateFor($user);

        $this->assertSame(1, $created);
        $this->assertCount(1, $user->fresh()->notifications);
        $this->assertCount(0, $otherUser->fresh()->notifications);
    }

    public function test_service_is_idempotent_for_same_alert(): void
    {
        $user = User::factory()->create();

        Task::factory()->create([
            'assigned_user_id' => $user->id,
            'status' => 'pending',
            'due_at' => now()->subDay(),
        ]);

        $service = app(CommercialNotificationService::class);

        $this->assertSame(1, $service->generateFor($user));
        $this->assertSame(0, $service->generateFor($user));
        $this->assertCount(1, $user->fresh()->notifications);
    }

    public function test_inactive_lead_generates_internal_alert(): void
    {
        config(['commercial.lead_inactivity_days' => 7]);

        $user = User::factory()->create();
        $lead = Lead::factory()->create([
            'assigned_user_id' => $user->id,
            'status' => 'contacted',
            'last_interaction_at' => now()->subDays(10),
            'created_at' => now()->subDays(20),
        ]);

        app(CommercialNotificationService::class)->generateFor($user);

        $notification = $user->fresh()->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame('lead_inactive', $notification->data['type']);
        $this->assertSame($lead->id, $notification->data['subject_id']);
    }
}
