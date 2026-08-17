<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(Activity::TYPES),
            'direction' => fake()->optional()->randomElement(
                Activity::DIRECTIONS,
            ),
            'subject' => fake()->sentence(5),
            'description' => fake()->optional()->paragraph(),
            'outcome' => fake()->optional()->sentence(),
            'company_id' => null,
            'contact_id' => null,
            'lead_id' => Lead::factory(),
            'opportunity_id' => null,
            'assigned_user_id' => User::factory(),
            'created_by_user_id' => User::factory(),
            'occurred_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'duration_minutes' => fake()->optional()->numberBetween(5, 120),
        ];
    }
}
