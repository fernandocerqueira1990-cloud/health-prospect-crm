<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(5),
            'description' => fake()->optional()->paragraph(),
            'status' => 'pending',
            'priority' => fake()->randomElement(Task::PRIORITIES),
            'is_follow_up' => false,
            'follow_up_channel' => null,
            'company_id' => null,
            'contact_id' => null,
            'lead_id' => null,
            'opportunity_id' => null,
            'completed_activity_id' => null,
            'assigned_user_id' => User::factory(),
            'created_by_user_id' => User::factory(),
            'due_at' => fake()->optional()->dateTimeBetween('now', '+30 days'),
            'started_at' => null,
            'completed_at' => null,
            'cancelled_at' => null,
        ];
    }
}
