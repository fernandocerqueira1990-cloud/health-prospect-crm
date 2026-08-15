<?php

namespace Tests\Feature\Imports;

use App\Services\ImportValueNormalizer;
use Tests\TestCase;

class ImportValueNormalizerTest extends TestCase
{
    public function test_semantic_normalizers_and_aliases_are_applied_deterministically(): void
    {
        $normalized = app(ImportValueNormalizer::class)->normalize([
            'Empresa' => '  Clínica   ABC  ', 'Email' => ' COMERCIAL@EMPRESA.COM ', 'Telefone' => '(71) 3333-4444',
            'Site' => 'empresa.com.br', 'Prioridade' => 'Alta', 'País' => ' br ', 'CNPJ' => '12.345.678/0001-90',
            'Temperatura' => 'Quente', 'Score' => '42',
        ], [
            'Empresa' => 'company.trade_name', 'Email' => 'company.email', 'Telefone' => 'company.phone',
            'Site' => 'company.website', 'Prioridade' => 'company.priority', 'País' => 'company.tax_id_country',
            'CNPJ' => 'company.tax_id', 'Temperatura' => 'lead.temperature', 'Score' => 'lead.score',
        ]);

        $this->assertSame('Clínica ABC', $normalized['company']['trade_name']);
        $this->assertSame('comercial@empresa.com', $normalized['company']['email']);
        $this->assertSame('7133334444', $normalized['company']['phone']);
        $this->assertSame('https://empresa.com.br', $normalized['company']['website']);
        $this->assertSame('high', $normalized['company']['priority']);
        $this->assertSame('BR', $normalized['company']['tax_id_country']);
        $this->assertSame('12345678000190', $normalized['company']['tax_id']);
        $this->assertSame('hot', $normalized['lead']['temperature']);
        $this->assertSame(42, $normalized['lead']['score']);
    }

    public function test_tax_id_without_country_and_unknown_values_are_preserved_conservatively(): void
    {
        $normalized = app(ImportValueNormalizer::class)->normalize([
            'CNPJ' => '12.345.678/0001-90', 'Prioridade' => 'Urgente', 'Score' => '42,5', 'Fórmula' => '=1+1',
        ], [
            'CNPJ' => 'company.tax_id', 'Prioridade' => 'lead.priority', 'Score' => 'lead.score', 'Fórmula' => 'lead.notes',
        ]);

        $this->assertSame('12.345.678/0001-90', $normalized['company']['tax_id']);
        $this->assertSame('Urgente', $normalized['lead']['priority']);
        $this->assertSame('42,5', $normalized['lead']['score']);
        $this->assertSame('=1+1', $normalized['lead']['notes']);
    }

    public function test_empty_values_do_not_create_empty_groups_and_zero_is_preserved(): void
    {
        $normalized = app(ImportValueNormalizer::class)->normalize(['Empresa' => ' ', 'Score' => 0], ['Empresa' => 'company.trade_name', 'Score' => 'lead.score']);

        $this->assertArrayNotHasKey('company', $normalized);
        $this->assertSame(0, $normalized['lead']['score']);
    }
}
