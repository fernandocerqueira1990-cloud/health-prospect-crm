<?php

namespace Tests\Feature\Imports;

use App\Support\ImportFieldCatalog;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ImportFieldCatalogTest extends TestCase
{
    public function test_catalog_contains_unique_allowed_targets_with_consistent_groups(): void
    {
        $catalog = app(ImportFieldCatalog::class);
        $targets = $catalog->targets();

        $this->assertCount(40, $targets);
        $this->assertSame($targets, array_values(array_unique($targets)));
        $this->assertSame(['company', 'contact', 'lead'], array_keys($catalog->groups()));
        $this->assertTrue($catalog->allows('company.trade_name'));
        $this->assertTrue($catalog->allows('contact.whatsapp'));
        $this->assertTrue($catalog->allows('lead.score'));
    }

    public function test_internal_relationship_targets_are_not_exposed(): void
    {
        $targets = app(ImportFieldCatalog::class)->targets();

        foreach (['company.assigned_user_id', 'company.source_id', 'contact.company_id', 'lead.company_id', 'lead.contact_id', 'lead.assigned_user_id', 'lead.source_id', 'lead.channel_id', 'lead.first_touch_source_event_id', 'lead.last_touch_source_event_id'] as $target) {
            $this->assertNotContains($target, $targets);
        }
    }

    #[DataProvider('suggestionProvider')]
    public function test_high_confidence_suggestions(string $header, ?string $expected): void
    {
        $this->assertSame($expected, app(ImportFieldCatalog::class)->suggest($header));
    }

    public function test_all_official_template_headers_have_safe_suggestions(): void
    {
        foreach ($this->officialTemplateMapping() as $header => $target) {
            $this->assertSame($target, app(ImportFieldCatalog::class)->suggest($header), $header);
        }
    }

    /** @return array<string, array{string, string|null}> */
    public static function suggestionProvider(): array
    {
        return [
            'company accents' => ['  Razão   Social ', 'company.legal_name'],
            'company punctuation' => ['Nome-da-Empresa', 'company.trade_name'],
            'tax id' => ['CNPJ', 'company.tax_id'],
            'state' => ['UF', 'company.state'],
            'contact' => ['Nome do Contato', 'contact.name'],
            'linkedin' => ['LinkedIn', 'contact.linkedin_url'],
            'ambiguous name' => ['Nome', null],
            'ambiguous phone' => ['Telefone', null],
            'ambiguous email' => ['Email', null],
            'ambiguous status' => ['Status', null],
        ];
    }

    /** @return array<string, string> */
    private function officialTemplateMapping(): array
    {
        return [
            'empresa_nome_fantasia' => 'company.trade_name', 'empresa_razao_social' => 'company.legal_name',
            'empresa_cnpj' => 'company.tax_id', 'empresa_pais_id_fiscal' => 'company.tax_id_country',
            'empresa_segmento' => 'company.segment', 'empresa_categoria' => 'company.category',
            'empresa_site' => 'company.website', 'empresa_telefone' => 'company.phone',
            'empresa_email' => 'company.email', 'empresa_logradouro' => 'company.street',
            'empresa_numero' => 'company.number', 'empresa_complemento' => 'company.complement',
            'empresa_bairro' => 'company.district', 'empresa_cidade' => 'company.city',
            'empresa_estado_uf' => 'company.state', 'empresa_cep' => 'company.postal_code',
            'empresa_estimativa_funcionarios' => 'company.employee_count_estimate',
            'empresa_prioridade' => 'company.priority', 'empresa_observacoes' => 'company.notes',
            'contato_nome' => 'contact.name', 'contato_cargo' => 'contact.job_title',
            'contato_departamento' => 'contact.department', 'contato_email' => 'contact.email',
            'contato_telefone' => 'contact.phone', 'contato_whatsapp' => 'contact.whatsapp',
            'contato_linkedin' => 'contact.linkedin_url', 'contato_papel_decisao' => 'contact.decision_role',
            'contato_nivel_influencia' => 'contact.influence_level', 'contato_observacoes' => 'contact.notes',
            'lead_nome' => 'lead.name', 'lead_empresa' => 'lead.company_name', 'lead_cargo' => 'lead.job_title',
            'lead_email' => 'lead.email', 'lead_telefone' => 'lead.phone', 'lead_whatsapp' => 'lead.whatsapp',
            'lead_status' => 'lead.status', 'lead_prioridade' => 'lead.priority',
            'lead_temperatura' => 'lead.temperature', 'lead_score' => 'lead.score', 'lead_observacoes' => 'lead.notes',
        ];
    }
}
