<?php

namespace Database\Factories;

use App\Models\Pipeline;
use App\Models\Stage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Stage>
 */
class StageFactory extends Factory
{
    protected $model = Stage::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'pipeline_id' => Pipeline::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('####'),
            'position' => fake()->unique()->numberBetween(1, 30000),
            'probability' => fake()->numberBetween(0, 100),
            'type' => 'open',
            'active' => true,
        ];
    }
}
