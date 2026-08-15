<?php

namespace Tests\Feature\Timeline;

use App\Actions\Tasks\CompleteFollowUpAction;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Task;
use App\Models\User;
use App\Queries\CommercialTimelineQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommercialTimelineQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_timeline_contains_activity(): void
    {
        $company = Company::factory()->create();

        $activity = Activity::factory()->create([
            'subject' => 'Ligação comercial',
            'company_id' => $company->id,
            'lead_id' => null,
        ]);

        $timeline = app(CommercialTimelineQuery::class)
            ->paginate([]);

        $event = collect($timeline->items())
            ->firstWhere('source_id', $activity->id);

        $this->assertNotNull($event);
        $this->assertSame('activity', $event->event_type);
        $this->assertSame('Ligação comercial', $event->title);
    }

    public function test_timeline_contains_open_follow_up(): void
    {
        $company = Company::factory()->create();

        $task = Task::factory()->create([
            'title' => 'Retornar cliente',
            'is_follow_up' => true,
            'follow_up_channel' => 'whatsapp',
            'company_id' => $company->id,
            'due_at' => now()->addDay(),
        ]);

        $timeline = app(CommercialTimelineQuery::class)
            ->paginate([]);

        $event = collect($timeline->items())
            ->first(
                fn (object $event): bool => $event->event_type === 'follow_up'
                    && $event->source_id === $task->id,
            );

        $this->assertNotNull($event);
        $this->assertSame('whatsapp', $event->channel);
        $this->assertSame('pending', $event->status);
    }

    public function test_commercial_task_is_included(): void
    {
        $company = Company::factory()->create();

        $task = Task::factory()->create([
            'title' => 'Preparar proposta',
            'company_id' => $company->id,
            'is_follow_up' => false,
        ]);

        $timeline = app(CommercialTimelineQuery::class)
            ->paginate([]);

        $event = collect($timeline->items())
            ->first(
                fn (object $event): bool => $event->event_type === 'task'
                    && $event->source_id === $task->id,
            );

        $this->assertNotNull($event);
    }

    public function test_internal_task_without_commercial_link_is_excluded(): void
    {
        $task = Task::factory()->create([
            'title' => 'Organizar documentação interna',
            'company_id' => null,
            'contact_id' => null,
            'lead_id' => null,
            'opportunity_id' => null,
            'is_follow_up' => false,
        ]);

        $timeline = app(CommercialTimelineQuery::class)
            ->paginate([]);

        $event = collect($timeline->items())
            ->first(
                fn (object $event): bool => $event->event_type === 'task'
                    && $event->source_id === $task->id,
            );

        $this->assertNull($event);
    }

    public function test_completed_follow_up_is_represented_only_by_activity(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();

        $task = Task::factory()->create([
            'title' => 'Confirmar demonstração',
            'is_follow_up' => true,
            'follow_up_channel' => 'call',
            'company_id' => $company->id,
            'assigned_user_id' => $user->id,
            'due_at' => now(),
        ]);

        $this->actingAs($user);

        $task = app(CompleteFollowUpAction::class)->execute(
            $task,
            $user,
            'Demonstração confirmada.',
        );

        $timeline = app(CommercialTimelineQuery::class)
            ->paginate([]);

        $events = collect($timeline->items());

        $followUp = $events->first(
            fn (object $event): bool => $event->event_type === 'follow_up'
                && $event->source_id === $task->id,
        );

        $activity = $events->first(
            fn (object $event): bool => $event->event_type === 'activity'
                && $event->source_id === $task->completed_activity_id,
        );

        $this->assertNull($followUp);
        $this->assertNotNull($activity);
        $this->assertSame(
            'Confirmar demonstração',
            $activity->title,
        );
    }

    public function test_timeline_can_filter_by_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        Activity::factory()->create([
            'subject' => 'Empresa A',
            'company_id' => $companyA->id,
            'lead_id' => null,
        ]);

        Activity::factory()->create([
            'subject' => 'Empresa B',
            'company_id' => $companyB->id,
            'lead_id' => null,
        ]);

        $timeline = app(CommercialTimelineQuery::class)
            ->paginate([
                'company_id' => $companyA->id,
            ]);

        $titles = collect($timeline->items())
            ->pluck('title');

        $this->assertTrue($titles->contains('Empresa A'));
        $this->assertFalse($titles->contains('Empresa B'));
    }
}
