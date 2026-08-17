<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Permission> */
class PermissionFactory extends Factory
{
    public function definition(): array
    {
        $resource = fake()->unique()->word();

        return [
            'name' => ucfirst($resource).' view',
            'slug' => $resource.'.view',
            'description' => fake()->sentence(),
        ];
    }
}
