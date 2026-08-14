<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Company> */
class CompanyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'legal_name' => fake()->company().' Ltda.',
            'trade_name' => fake()->company(),
            'tax_id' => null,
            'segment' => 'Saúde',
            'category' => fake()->randomElement(['Clínica', 'Hospital', 'Laboratório']),
            'website' => fake()->url(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->companyEmail(),
            'city' => fake()->city(),
            'state' => 'SP',
            'priority' => fake()->randomElement(Company::PRIORITIES),
        ];
    }
}
