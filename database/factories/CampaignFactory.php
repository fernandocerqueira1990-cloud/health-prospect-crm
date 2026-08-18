<?php

namespace Database\Factories;

use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Campaign> */
class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-1 month', '+3 months');

        return [
            'name' => ucfirst(fake()->words(4, true)),
            'description' => fake()->optional()->paragraph(),
            'status' => fake()->randomElement(Campaign::STATUSES),
            'channel_id' => null,
            'owner_user_id' => null,
            'start_date' => $startDate,
            'end_date' => fake()->optional()->dateTimeBetween($startDate, '+6 months'),
            'budget' => fake()->optional()->randomFloat(2, 0, 500000),
            'currency' => 'BRL',
            'utm_source' => fake()->optional()->slug(2),
            'utm_medium' => fake()->optional()->slug(2),
            'utm_campaign' => fake()->optional()->slug(3),
            'utm_content' => null,
            'utm_term' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
