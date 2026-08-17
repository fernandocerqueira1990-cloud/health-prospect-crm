<?php

namespace Database\Factories;

use App\Models\LossReason;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LossReason>
 */
class LossReasonFactory extends Factory
{
    protected $model = LossReason::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('####'),
            'description' => fake()->optional()->sentence(),
            'position' => fake()->unique()->numberBetween(1, 30000),
            'active' => true,
        ];
    }
}
