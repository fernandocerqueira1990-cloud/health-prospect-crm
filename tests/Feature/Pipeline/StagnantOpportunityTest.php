<?php

namespace Tests\Feature\Pipeline;

use App\Models\Opportunity;
use App\Models\OpportunityStageHistory;
use App\Models\Pipeline;
use App\Models\Stage;
use App\Models\User;
use App\Queries\OpportunityIndexQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StagnantOpportunityTest extends TestCase
{
    use RefreshDatabase;

    public function test_stagnant_filter_returns_old_open_opportunity_without_recent_stage_change(): void
    {
        config()->set('commercial.opportunity_stagnation_days', 14);

        $pipeline = Pipeline::factory()->create();
        $stage = Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'type' => 'open',
        ]);
        $user = User::factory()->create();

        $stagnant = Opportunity::factory()->create([
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            'assigned_user_id' => $user->id,
            'created_at' => now()->subDays(30),
            'updated_at' => now()->subDays(30),
        ]);

        $fresh = Opportunity::factory()->create([
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            'assigned_user_id' => $user->id,
            'created_at' => now()->subDays(30),
            'updated_at' => now()->subDays(30),
        ]);

        OpportunityStageHistory::query()->create([
            'pipeline_id' => $pipeline->id,
            'opportunity_id' => $fresh->id,
            'from_stage_id' => null,
            'to_stage_id' => $stage->id,
            'changed_by_user_id' => $user->id,
            'changed_at' => now()->subDays(3),
            'notes' => null,
        ]);

        $result = app(OpportunityIndexQuery::class)->paginate([
            'stagnant' => true,
            'per_page' => 15,
        ]);

        $this->assertTrue($result->getCollection()->contains($stagnant));
        $this->assertFalse($result->getCollection()->contains($fresh));
    }

    public function test_terminal_stage_is_never_considered_stagnant(): void
    {
        config()->set('commercial.opportunity_stagnation_days', 14);

        $pipeline = Pipeline::factory()->create();
        $wonStage = Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'type' => 'won',
        ]);

        $won = Opportunity::factory()->create([
            'pipeline_id' => $pipeline->id,
            'stage_id' => $wonStage->id,
            'created_at' => now()->subDays(60),
            'updated_at' => now()->subDays(60),
        ]);

        $result = app(OpportunityIndexQuery::class)->paginate([
            'stagnant' => true,
            'per_page' => 15,
        ]);

        $this->assertFalse($result->getCollection()->contains($won));
    }

    public function test_recently_created_open_opportunity_is_not_stagnant(): void
    {
        config()->set('commercial.opportunity_stagnation_days', 14);

        $pipeline = Pipeline::factory()->create();
        $stage = Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'type' => 'open',
        ]);

        $recent = Opportunity::factory()->create([
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        $result = app(OpportunityIndexQuery::class)->paginate([
            'stagnant' => true,
            'per_page' => 15,
        ]);

        $this->assertFalse($result->getCollection()->contains($recent));
    }
}
