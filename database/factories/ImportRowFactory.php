<?php

namespace Database\Factories;

use App\Models\DataImport;
use App\Models\ImportRow;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ImportRow> */
class ImportRowFactory extends Factory
{
    protected $model = ImportRow::class;

    public function definition(): array
    {
        return ['import_id' => DataImport::factory(), 'row_number' => 2, 'status' => ImportRow::STATUS_PARSED, 'original_data' => ['Nome' => fake()->name()]];
    }
}
