<?php

namespace Tests\Feature\Pipeline;

use App\Models\Pipeline;
use App\Models\Stage;
use Database\Seeders\PipelineSeeder;
use Database\Seeders\StageSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PipelineStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_pipeline_and_stage_seeders_are_idempotent(): void
    {
        $this->seed([
            PipelineSeeder::class,
            StageSeeder::class,
        ]);

        $this->seed([
            PipelineSeeder::class,
            StageSeeder::class,
        ]);

        $pipeline = Pipeline::query()
            ->where('slug', 'comercial')
            ->firstOrFail();

        $this->assertTrue($pipeline->is_default);
        $this->assertTrue($pipeline->active);

        $this->assertSame(1, Pipeline::query()->count());
        $this->assertSame(8, Stage::query()->count());

        $this->assertSame(
            [
                'novo',
                'qualificacao',
                'diagnostico',
                'demonstracao',
                'proposta',
                'negociacao',
                'ganho',
                'perdido',
            ],
            $pipeline->stages()->pluck('slug')->all(),
        );
    }

    public function test_database_allows_only_one_default_pipeline(): void
    {
        Pipeline::factory()->create([
            'is_default' => true,
        ]);

        $this->expectException(QueryException::class);

        Pipeline::factory()->create([
            'is_default' => true,
        ]);
    }

    public function test_stage_probability_cannot_exceed_one_hundred(): void
    {
        $pipeline = Pipeline::factory()->create();

        $this->expectException(QueryException::class);

        Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'probability' => 101,
        ]);
    }

    public function test_stage_type_must_be_supported(): void
    {
        $pipeline = Pipeline::factory()->create();

        $this->expectException(QueryException::class);

        Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'type' => 'invalid',
        ]);
    }

    public function test_stage_position_must_be_positive(): void
    {
        $pipeline = Pipeline::factory()->create();

        $this->expectException(QueryException::class);

        Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'position' => 0,
        ]);
    }

    public function test_stage_slug_is_unique_inside_pipeline(): void
    {
        $pipeline = Pipeline::factory()->create();

        Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'slug' => 'proposta',
            'position' => 1,
        ]);

        $this->expectException(QueryException::class);

        Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'slug' => 'proposta',
            'position' => 2,
        ]);
    }

    public function test_stage_position_is_unique_inside_pipeline(): void
    {
        $pipeline = Pipeline::factory()->create();

        Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'slug' => 'primeiro',
            'position' => 1,
        ]);

        $this->expectException(QueryException::class);

        Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'slug' => 'segundo',
            'position' => 1,
        ]);
    }

    public function test_same_slug_and_position_are_allowed_in_different_pipelines(): void
    {
        $pipelineA = Pipeline::factory()->create();
        $pipelineB = Pipeline::factory()->create();

        Stage::factory()->create([
            'pipeline_id' => $pipelineA->id,
            'slug' => 'qualificacao',
            'position' => 1,
        ]);

        $stage = Stage::factory()->create([
            'pipeline_id' => $pipelineB->id,
            'slug' => 'qualificacao',
            'position' => 1,
        ]);

        $this->assertSame($pipelineB->id, $stage->pipeline_id);
    }

    public function test_pipeline_stages_are_returned_in_position_order(): void
    {
        $pipeline = Pipeline::factory()->create();

        Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'slug' => 'terceiro',
            'position' => 3,
        ]);

        Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'slug' => 'primeiro',
            'position' => 1,
        ]);

        Stage::factory()->create([
            'pipeline_id' => $pipeline->id,
            'slug' => 'segundo',
            'position' => 2,
        ]);

        $this->assertSame(
            [1, 2, 3],
            $pipeline->stages()->pluck('position')->all(),
        );
    }
}
