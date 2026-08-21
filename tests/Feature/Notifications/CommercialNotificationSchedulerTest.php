<?php

namespace Tests\Feature\Notifications;

use App\Jobs\GenerateCommercialNotificationsForUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CommercialNotificationSchedulerTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_dispatches_one_job_for_each_active_user_only(): void
    {
        Queue::fake();

        $first = User::factory()->create(['active' => true]);
        $second = User::factory()->create(['active' => true]);
        User::factory()->create(['active' => false]);

        $this->artisan('commercial:notifications')
            ->expectsOutput('2 usuário(s) enfileirados para geração de alertas comerciais.')
            ->assertSuccessful();

        Queue::assertPushed(GenerateCommercialNotificationsForUser::class, 2);
        Queue::assertPushed(
            GenerateCommercialNotificationsForUser::class,
            fn (GenerateCommercialNotificationsForUser $job): bool => $job->userId === $first->id,
        );
        Queue::assertPushed(
            GenerateCommercialNotificationsForUser::class,
            fn (GenerateCommercialNotificationsForUser $job): bool => $job->userId === $second->id,
        );
    }

    public function test_commercial_notification_job_has_stable_uniqueness_per_user(): void
    {
        $user = User::factory()->create(['active' => true]);

        $job = new GenerateCommercialNotificationsForUser($user->id);

        $this->assertSame((string) $user->id, $job->uniqueId());
        $this->assertSame(3600, $job->uniqueFor);
        $this->assertSame(3, $job->tries);
    }
}
