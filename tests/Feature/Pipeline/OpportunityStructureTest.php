<?php

namespace Tests\Feature\Pipeline;

use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\Stage;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpportunityStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_opportunity_with_stage_from_same_pipeline(): void
    {
        $opportunity = Opportunity::factory()->create();

        $this->assertNotNull($opportunity->lead_id);

        $this->assertSame(
            $opportunity->pipeline_id,
            $opportunity->stage->pipeline_id,
        );
    }

    public function test_database_rejects_stage_from_another_pipeline(): void
    {
        $pipelineA = Pipeline::factory()->create();
        $pipelineB = Pipeline::factory()->create();

        $stageB = Stage::factory()->create([
            'pipeline_id' => $pipelineB->id,
        ]);

        $lead = Lead::factory()->create();

        $this->expectException(QueryException::class);

        Opportunity::create([
            'title' => 'Pipeline incompatível',
            'lead_id' => $lead->id,
            'pipeline_id' => $pipelineA->id,
            'stage_id' => $stageB->id,
        ]);
    }

    public function test_probability_cannot_exceed_one_hundred(): void
    {
        $pipeline = Pipeline::factory()->create();

        $stage = Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
        ]);

        $lead = Lead::factory()->create();

        $this->expectException(QueryException::class);

        Opportunity::create([
            'title' => 'Probabilidade inválida',
            'lead_id' => $lead->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            'probability' => 101,
        ]);
    }

    public function test_amount_cannot_be_negative(): void
    {
        $pipeline = Pipeline::factory()->create();

        $stage = Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
        ]);

        $lead = Lead::factory()->create();

        $this->expectException(QueryException::class);

        Opportunity::create([
            'title' => 'Valor inválido',
            'lead_id' => $lead->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            'amount' => -1,
        ]);
    }

    public function test_currency_must_use_three_uppercase_letters(): void
    {
        $pipeline = Pipeline::factory()->create();

        $stage = Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
        ]);

        $lead = Lead::factory()->create();

        $this->expectException(QueryException::class);

        Opportunity::create([
            'title' => 'Moeda inválida',
            'lead_id' => $lead->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            'currency' => 'brl',
        ]);
    }

    public function test_opportunity_requires_lead_or_company(): void
    {
        $pipeline = Pipeline::factory()->create();

        $stage = Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
        ]);

        $this->expectException(QueryException::class);

        Opportunity::create([
            'title' => 'Sem vínculo comercial',
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
        ]);
    }

    public function test_opportunity_cannot_be_won_and_lost_simultaneously(): void
    {
        $pipeline = Pipeline::factory()->create();

        $stage = Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
        ]);

        $lead = Lead::factory()->create();

        $this->expectException(QueryException::class);

        Opportunity::create([
            'title' => 'Estado terminal inválido',
            'lead_id' => $lead->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            'won_at' => now(),
            'lost_at' => now(),
        ]);
    }

    public function test_inverse_relations_expose_opportunity(): void
    {
        $pipeline = Pipeline::factory()->create();

        $stage = Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
        ]);

        $lead = Lead::factory()->create();

        $opportunity = Opportunity::factory()->create([
            'lead_id' => $lead->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
        ]);

        $this->assertTrue(
            $lead->opportunities()->whereKey($opportunity->id)->exists(),
        );

        $this->assertTrue(
            $pipeline->opportunities()->whereKey($opportunity->id)->exists(),
        );

        $this->assertTrue(
            $stage->opportunities()->whereKey($opportunity->id)->exists(),
        );
    }
}
