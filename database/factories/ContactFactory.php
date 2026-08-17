<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Contact> */
class ContactFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->name(),
            'job_title' => fake()->jobTitle(),
            'department' => fake()->randomElement(['TI', 'Compras', 'Financeiro', 'Diretoria']),
            'email' => fake()->safeEmail(),
            'phone' => '+5511'.fake()->numerify('9########'),
            'whatsapp' => '+5511'.fake()->numerify('9########'),
            'linkedin_url' => 'https://www.linkedin.com/in/'.fake()->slug(),
            'decision_role' => fake()->randomElement(Contact::DECISION_ROLES),
            'influence_level' => fake()->randomElement(Contact::INFLUENCE_LEVELS),
            'is_primary' => false,
            'active' => true,
        ];
    }
}
