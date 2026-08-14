<?php

namespace Database\Factories;

use App\Models\Channel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Channel>
 */
class ChannelFactory extends Factory
{
    protected $model = Channel::class;

    public function definition(): array
    {
        $name = 'Canal '.fake()->unique()->numerify('#####');

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'active' => true,
        ];
    }
}
