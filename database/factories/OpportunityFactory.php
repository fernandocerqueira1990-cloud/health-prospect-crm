<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Opportunity>
 */
class OpportunityFactory extends Factory
{
    protected $model = Opportunity::class;

    public function definition(): array
    {
        return [
            'title' => ucfirst(fake()->words(4, true)),

            'lead_id' => Lead::factory(),
            'company_id' => null,
            'contact_id' => null,
            'assigned_user_id' => User::factory(),

            'pipeline_id' => Pipeline::factory(),

            'stage_id' => function (array $attributes): int {
                return Stage::factory()->create([
                    'pipeline_id' => $attributes['pipeline_id'],
                    'type' => 'open',
                ])->id;
            },

            'amount' => fake()->randomFloat(2, 0, 500000),
            'currency' => 'BRL',
            'probability' => fake()->numberBetween(0, 100),

            'expected_close_date' => fake()->optional()->dateTimeBetween(
                'now',
                '+6 months',
            ),

            'won_at' => null,
            'lost_at' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
