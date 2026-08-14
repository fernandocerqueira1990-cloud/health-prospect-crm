<?php

namespace Database\Factories;

use App\Models\Channel;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'company_name' => fake()->company(),
            'job_title' => fake()->jobTitle(),
            'email' => fake()->safeEmail(),
            'phone' => '+5571'.fake()->numerify('9########'),
            'whatsapp' => '+5571'.fake()->numerify('9########'),

            'company_id' => null,
            'contact_id' => null,
            'assigned_user_id' => User::factory(),

            'source_id' => LeadSource::factory(),
            'channel_id' => Channel::factory(),

            'status' => 'new',
            'priority' => fake()->randomElement(Lead::PRIORITIES),
            'temperature' => fake()->randomElement(Lead::TEMPERATURES),
            'score' => fake()->numberBetween(0, 100),

            'qualified_at' => null,
            'converted_at' => null,
            'lost_at' => null,
            'last_interaction_at' => null,
            'next_action_at' => null,

            'notes' => null,
        ];
    }
}
