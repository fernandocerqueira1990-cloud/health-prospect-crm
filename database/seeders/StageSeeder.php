<?php

namespace Database\Seeders;

use App\Models\Pipeline;
use App\Models\Stage;
use Illuminate\Database\Seeder;

class StageSeeder extends Seeder
{
    public function run(): void
    {
        $pipeline = Pipeline::query()
            ->where('slug', 'comercial')
            ->firstOrFail();

        $stages = [
            [
                'name' => 'Novo',
                'slug' => 'novo',
                'position' => 1,
                'probability' => 10,
                'type' => 'open',
            ],
            [
                'name' => 'Qualificação',
                'slug' => 'qualificacao',
                'position' => 2,
                'probability' => 20,
                'type' => 'open',
            ],
            [
                'name' => 'Diagnóstico',
                'slug' => 'diagnostico',
                'position' => 3,
                'probability' => 35,
                'type' => 'open',
            ],
            [
                'name' => 'Demonstração',
                'slug' => 'demonstracao',
                'position' => 4,
                'probability' => 50,
                'type' => 'open',
            ],
            [
                'name' => 'Proposta',
                'slug' => 'proposta',
                'position' => 5,
                'probability' => 70,
                'type' => 'open',
            ],
            [
                'name' => 'Negociação',
                'slug' => 'negociacao',
                'position' => 6,
                'probability' => 85,
                'type' => 'open',
            ],
            [
                'name' => 'Ganho',
                'slug' => 'ganho',
                'position' => 7,
                'probability' => 100,
                'type' => 'won',
            ],
            [
                'name' => 'Perdido',
                'slug' => 'perdido',
                'position' => 8,
                'probability' => 0,
                'type' => 'lost',
            ],
        ];

        foreach ($stages as $stage) {
            Stage::query()->updateOrCreate(
                [
                    'pipeline_id' => $pipeline->id,
                    'slug' => $stage['slug'],
                ],
                [
                    ...$stage,
                    'active' => true,
                ],
            );
        }
    }
}
