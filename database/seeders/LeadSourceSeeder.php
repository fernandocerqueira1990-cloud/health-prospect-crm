<?php

namespace Database\Seeders;

use App\Models\LeadSource;
use Illuminate\Database\Seeder;

class LeadSourceSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            ['name' => 'Prospecção ativa', 'slug' => 'prospeccao-ativa'],
            ['name' => 'Indicação', 'slug' => 'indicacao'],
            ['name' => 'Site / Inbound', 'slug' => 'site-inbound'],
            ['name' => 'Evento', 'slug' => 'evento'],
            ['name' => 'Parceiro', 'slug' => 'parceiro'],
            ['name' => 'Lista comercial', 'slug' => 'lista-comercial'],
            ['name' => 'Importação', 'slug' => 'importacao'],
            ['name' => 'Outro', 'slug' => 'outro'],
        ];

        foreach ($sources as $source) {
            LeadSource::updateOrCreate(
                ['slug' => $source['slug']],
                [
                    'name' => $source['name'],
                    'active' => true,
                ],
            );
        }
    }
}
