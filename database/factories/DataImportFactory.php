<?php

namespace Database\Factories;

use App\Models\DataImport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DataImport> */
class DataImportFactory extends Factory
{
    protected $model = DataImport::class;

    public function definition(): array
    {
        return ['user_id' => User::factory(), 'filename' => fake()->uuid().'.csv', 'original_filename' => 'contatos.csv', 'type' => DataImport::TYPE_CSV, 'status' => DataImport::STATUS_PARSED, 'total_rows' => 0, 'metadata' => ['delimiter' => ',']];
    }
}
