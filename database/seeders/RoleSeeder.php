<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public const ROLES = [
        'admin' => ['Administrador', 'Acesso administrativo total.'],
        'sales_manager' => ['Gestor Comercial', 'Gestão da operação comercial.'],
        'supervisor' => ['Supervisor', 'Supervisão da equipe comercial.'],
        'sales_rep' => ['Vendedor', 'Operação comercial e relacionamento.'],
        'marketing' => ['Marketing', 'Campanhas e análise de aquisição.'],
        'analyst' => ['Analista', 'Análise e relatórios.'],
        'readonly' => ['Somente Leitura', 'Consulta sem alteração de dados.'],
        'tester' => ['Usuário de Teste', 'Acesso aos módulos operacionais para testes do CRM.'],
    ];

    public function run(): void
    {
        foreach (self::ROLES as $slug => [$name, $description]) {
            Role::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'description' => $description,
                'active' => true,
            ]);
        }
    }
}
