<?php

namespace Database\Seeders;

use App\Models\Pipeline;
use Illuminate\Database\Seeder;

class PipelineSeeder extends Seeder
{
    public function run(): void
    {
        $pipeline = Pipeline::query()->updateOrCreate(
            ['slug' => 'comercial'],
            [
                'name' => 'Pipeline Comercial',
                'description' => 'Funil comercial principal do CRM.',
                'active' => true,
            ],
        );

        $hasDefault = Pipeline::query()
            ->where('is_default', true)
            ->whereKeyNot($pipeline->id)
            ->exists();

        if (! $hasDefault && ! $pipeline->is_default) {
            $pipeline->update([
                'is_default' => true,
            ]);
        }
    }
}
