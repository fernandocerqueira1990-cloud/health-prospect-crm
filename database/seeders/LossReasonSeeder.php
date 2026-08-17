<?php

namespace Database\Seeders;

use App\Models\LossReason;
use Illuminate\Database\Seeder;

class LossReasonSeeder extends Seeder
{
    public function run(): void
    {
        $reasons = [
            [
                'name' => 'Preço',
                'slug' => 'preco',
                'description' => 'Cliente considerou o preço ou investimento inadequado.',
                'position' => 1,
            ],
            [
                'name' => 'Sem orçamento',
                'slug' => 'sem-orcamento',
                'description' => 'Cliente não possui orçamento disponível no momento.',
                'position' => 2,
            ],
            [
                'name' => 'Concorrente',
                'slug' => 'concorrente',
                'description' => 'Cliente optou por uma solução concorrente.',
                'position' => 3,
            ],
            [
                'name' => 'Sem decisão',
                'slug' => 'sem-decisao',
                'description' => 'Processo não avançou por ausência de decisão.',
                'position' => 4,
            ],
            [
                'name' => 'Momento inadequado',
                'slug' => 'momento-inadequado',
                'description' => 'Projeto foi adiado ou não é prioridade neste momento.',
                'position' => 5,
            ],
            [
                'name' => 'Sem aderência',
                'slug' => 'sem-aderencia',
                'description' => 'Solução não apresentou aderência suficiente à necessidade.',
                'position' => 6,
            ],
            [
                'name' => 'Sem contato',
                'slug' => 'sem-contato',
                'description' => 'Não foi possível manter contato com o prospect.',
                'position' => 7,
            ],
            [
                'name' => 'Outro',
                'slug' => 'outro',
                'description' => 'Outro motivo de perda.',
                'position' => 8,
            ],
        ];

        foreach ($reasons as $reason) {
            LossReason::query()->updateOrCreate(
                ['slug' => $reason['slug']],
                [
                    ...$reason,
                    'active' => true,
                ],
            );
        }
    }
}
