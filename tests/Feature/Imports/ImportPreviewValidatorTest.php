<?php

namespace Tests\Feature\Imports;

use App\Services\ImportPreviewValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ImportPreviewValidatorTest extends TestCase
{
    public function test_valid_values_include_email_cnpj_enums_integers_zero_and_null(): void
    {
        $result = $this->validator()->validate([
            'company' => ['legal_name' => 'Clínica Vida', 'email' => 'contato@clinica.test', 'website' => 'https://clinica.test', 'tax_id_country' => 'BR', 'tax_id' => '11222333000181', 'priority' => 'high', 'employee_count_estimate' => 0, 'phone' => '+5571999999999'],
            'contact' => ['name' => 'Ana', 'decision_role' => 'decision_maker', 'influence_level' => 'high', 'email' => null],
            'lead' => ['email' => 'lead@example.test', 'status' => 'new', 'priority' => 'medium', 'temperature' => 'warm', 'score' => 0],
        ], [
            'company.legal_name', 'company.email', 'company.website', 'company.tax_id_country', 'company.tax_id', 'company.priority', 'company.employee_count_estimate', 'company.phone',
            'contact.name', 'contact.decision_role', 'contact.influence_level', 'contact.email',
            'lead.email', 'lead.status', 'lead.priority', 'lead.temperature', 'lead.score',
        ]);

        $this->assertSame('valid', $result['status']);
        $this->assertSame([], $result['issues']);
    }

    #[DataProvider('invalidValueProvider')]
    public function test_schema_backed_invalid_values_are_errors(array $data, array $targets, string $code): void
    {
        $result = $this->validator()->validate($data, $targets);

        $this->assertSame('error', $result['status']);
        $this->assertContains($code, array_column($result['issues'], 'code'));
    }

    /** @return array<string, array{array<string, mixed>, list<string>, string}> */
    public static function invalidValueProvider(): array
    {
        return [
            'invalid email' => [['lead' => ['email' => 'invalido']], ['lead.email'], 'invalid_email'],
            'invalid brazilian tax id' => [['company' => ['legal_name' => 'Empresa', 'tax_id_country' => 'BR', 'tax_id' => '12345678000190']], ['company.legal_name', 'company.tax_id_country', 'company.tax_id'], 'invalid_tax_id'],
            'invalid international tax id' => [['company' => ['legal_name' => 'Empresa', 'tax_id_country' => 'US', 'tax_id' => '@invalid']], ['company.legal_name', 'company.tax_id_country', 'company.tax_id'], 'invalid_tax_id'],
            'invalid website' => [['company' => ['legal_name' => 'Empresa', 'website' => 'https://domínio inválido.test']], ['company.legal_name', 'company.website'], 'invalid_url'],
            'invalid enum' => [['lead' => ['name' => 'Lead', 'status' => 'unknown']], ['lead.name', 'lead.status'], 'invalid_enum'],
            'non integer' => [['lead' => ['name' => 'Lead', 'score' => '10.5']], ['lead.name', 'lead.score'], 'invalid_integer'],
            'negative integer' => [['company' => ['legal_name' => 'Empresa', 'employee_count_estimate' => -1]], ['company.legal_name', 'company.employee_count_estimate'], 'invalid_integer'],
            'score over constraint' => [['lead' => ['name' => 'Lead', 'score' => 101]], ['lead.name', 'lead.score'], 'invalid_integer'],
            'value too long' => [['company' => ['legal_name' => str_repeat('a', 256)]], ['company.legal_name'], 'value_too_long'],
            'boolean is not textual' => [['company' => ['legal_name' => true]], ['company.legal_name'], 'invalid_type'],
            'missing company identity' => [['company' => ['city' => 'Recife']], ['company.city'], 'missing_required_value'],
            'missing contact identity' => [['contact' => ['email' => 'ana@example.test']], ['contact.email'], 'missing_required_value'],
            'missing lead identity' => [['lead' => ['status' => 'new']], ['lead.status'], 'missing_required_value'],
        ];
    }

    public function test_tax_id_without_country_and_ambiguous_phone_are_warnings(): void
    {
        $result = $this->validator()->validate([
            'company' => ['legal_name' => 'Empresa', 'tax_id' => 'ABC-123', 'phone' => '123'],
        ], ['company.legal_name', 'company.tax_id', 'company.phone']);

        $this->assertSame('warning', $result['status']);
        $this->assertEqualsCanonicalizing(['tax_country_missing', 'invalid_phone'], array_column($result['issues'], 'code'));
    }

    public function test_empty_normalized_data_is_a_warning(): void
    {
        $result = $this->validator()->validate([], ['company.legal_name']);

        $this->assertSame('warning', $result['status']);
        $this->assertSame(['no_mapped_data'], array_column($result['issues'], 'code'));
    }

    public function test_error_has_precedence_over_warning_and_unmapped_fields_are_not_validated(): void
    {
        $result = $this->validator()->validate([
            'company' => ['legal_name' => 'Empresa', 'phone' => '123'],
            'lead' => ['email' => 'invalido', 'score' => 'também inválido'],
        ], ['company.legal_name', 'company.phone', 'lead.email']);

        $this->assertSame('error', $result['status']);
        $this->assertContains('invalid_phone', array_column($result['issues'], 'code'));
        $this->assertContains('invalid_email', array_column($result['issues'], 'code'));
        $this->assertNotContains('invalid_integer', array_column($result['issues'], 'code'));
    }

    private function validator(): ImportPreviewValidator
    {
        return app(ImportPreviewValidator::class);
    }
}
