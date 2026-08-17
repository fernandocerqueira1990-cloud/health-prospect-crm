<?php

namespace Tests\Feature\Imports;

use App\Services\ImportPreviewValidator;
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

    public function test_brazil_name_and_code_are_normalized_case_insensitively_with_trim(): void
    {
        $normalizer = app(ImportValueNormalizer::class);

        $this->assertSame('BR', $normalizer->normalize(['País' => '  Brasil  '], ['País' => 'company.tax_id_country'])['company']['tax_id_country']);
        $this->assertSame('BR', $normalizer->normalize(['País' => ' br '], ['País' => 'company.tax_id_country'])['company']['tax_id_country']);
    }

    public function test_all_import_enum_fields_accept_friendly_portuguese_values(): void
    {
        $normalized = app(ImportValueNormalizer::class)->normalize([
            'Empresa prioridade' => ' A ',
            'Papel' => ' Decisor ',
            'Influência' => ' ALTO ',
            'Status' => ' novo ',
            'Lead prioridade' => 'a',
            'Temperatura' => ' Morno ',
        ], [
            'Empresa prioridade' => 'company.priority',
            'Papel' => 'contact.decision_role',
            'Influência' => 'contact.influence_level',
            'Status' => 'lead.status',
            'Lead prioridade' => 'lead.priority',
            'Temperatura' => 'lead.temperature',
        ]);

        $this->assertSame('high', $normalized['company']['priority']);
        $this->assertSame('decision_maker', $normalized['contact']['decision_role']);
        $this->assertSame('high', $normalized['contact']['influence_level']);
        $this->assertSame('new', $normalized['lead']['status']);
        $this->assertSame('high', $normalized['lead']['priority']);
        $this->assertSame('warm', $normalized['lead']['temperature']);
    }

    public function test_unknown_country_and_enum_values_remain_invalid_candidates(): void
    {
        $normalized = app(ImportValueNormalizer::class)->normalize([
            'País' => '  Atlântida  ', 'Status' => '  Pendente externo  ',
        ], [
            'País' => 'company.tax_id_country', 'Status' => 'lead.status',
        ]);

        $this->assertSame('Atlântida', $normalized['company']['tax_id_country']);
        $this->assertSame('Pendente externo', $normalized['lead']['status']);

        $validation = app(ImportPreviewValidator::class)->validate($normalized, ['company.tax_id_country', 'lead.status']);
        $codes = array_column($validation['issues'], 'code');
        $this->assertContains('invalid_country', $codes);
        $this->assertContains('invalid_enum', $codes);
    }
}
