<?php

namespace App\Support;

use Illuminate\Support\Str;

class ImportFieldCatalog
{
    /** @var array<string, array{label: string, fields: array<string, string>}> */
    private const GROUPS = [
        'company' => [
            'label' => 'Empresa',
            'fields' => [
                'company.trade_name' => 'Nome fantasia', 'company.legal_name' => 'Razão social',
                'company.tax_id' => 'CNPJ / Identificação fiscal', 'company.tax_id_country' => 'País da identificação fiscal',
                'company.segment' => 'Segmento', 'company.category' => 'Categoria', 'company.website' => 'Site',
                'company.phone' => 'Telefone', 'company.email' => 'E-mail', 'company.street' => 'Logradouro',
                'company.number' => 'Número', 'company.complement' => 'Complemento', 'company.district' => 'Bairro',
                'company.city' => 'Cidade', 'company.state' => 'Estado / UF', 'company.postal_code' => 'CEP',
                'company.employee_count_estimate' => 'Estimativa de funcionários', 'company.priority' => 'Prioridade',
                'company.notes' => 'Observações',
            ],
        ],
        'contact' => [
            'label' => 'Contato',
            'fields' => [
                'contact.name' => 'Nome', 'contact.job_title' => 'Cargo', 'contact.department' => 'Departamento',
                'contact.email' => 'E-mail', 'contact.phone' => 'Telefone', 'contact.whatsapp' => 'WhatsApp',
                'contact.linkedin_url' => 'LinkedIn', 'contact.decision_role' => 'Papel na decisão',
                'contact.influence_level' => 'Nível de influência', 'contact.notes' => 'Observações',
            ],
        ],
        'lead' => [
            'label' => 'Lead',
            'fields' => [
                'lead.name' => 'Nome', 'lead.company_name' => 'Empresa', 'lead.job_title' => 'Cargo',
                'lead.email' => 'E-mail', 'lead.phone' => 'Telefone', 'lead.whatsapp' => 'WhatsApp',
                'lead.status' => 'Status', 'lead.priority' => 'Prioridade', 'lead.temperature' => 'Temperatura',
                'lead.score' => 'Score', 'lead.notes' => 'Observações',
            ],
        ],
    ];

    /** @var array<string, string> */
    private const SUGGESTIONS = [
        'nome da empresa' => 'company.trade_name', 'empresa' => 'company.trade_name', 'nome fantasia' => 'company.trade_name',
        'razao social' => 'company.legal_name', 'cnpj' => 'company.tax_id', 'bairro' => 'company.district',
        'cidade' => 'company.city', 'estado' => 'company.state', 'uf' => 'company.state', 'cep' => 'company.postal_code',
        'site' => 'company.website', 'website' => 'company.website', 'nome do contato' => 'contact.name',
        'contato' => 'contact.name', 'cargo do contato' => 'contact.job_title', 'linkedin' => 'contact.linkedin_url',
    ];

    /** @var array<string, string> */
    private const OFFICIAL_TEMPLATE_HEADERS = [
        'empresa_nome_fantasia' => 'company.trade_name',
        'empresa_razao_social' => 'company.legal_name',
        'empresa_cnpj' => 'company.tax_id',
        'empresa_pais_id_fiscal' => 'company.tax_id_country',
        'empresa_segmento' => 'company.segment',
        'empresa_categoria' => 'company.category',
        'empresa_site' => 'company.website',
        'empresa_telefone' => 'company.phone',
        'empresa_email' => 'company.email',
        'empresa_logradouro' => 'company.street',
        'empresa_numero' => 'company.number',
        'empresa_complemento' => 'company.complement',
        'empresa_bairro' => 'company.district',
        'empresa_cidade' => 'company.city',
        'empresa_estado_uf' => 'company.state',
        'empresa_cep' => 'company.postal_code',
        'empresa_estimativa_funcionarios' => 'company.employee_count_estimate',
        'empresa_prioridade' => 'company.priority',
        'empresa_observacoes' => 'company.notes',
        'contato_nome' => 'contact.name',
        'contato_cargo' => 'contact.job_title',
        'contato_departamento' => 'contact.department',
        'contato_email' => 'contact.email',
        'contato_telefone' => 'contact.phone',
        'contato_whatsapp' => 'contact.whatsapp',
        'contato_linkedin' => 'contact.linkedin_url',
        'contato_papel_decisao' => 'contact.decision_role',
        'contato_nivel_influencia' => 'contact.influence_level',
        'contato_observacoes' => 'contact.notes',
        'lead_nome' => 'lead.name',
        'lead_empresa' => 'lead.company_name',
        'lead_cargo' => 'lead.job_title',
        'lead_email' => 'lead.email',
        'lead_telefone' => 'lead.phone',
        'lead_whatsapp' => 'lead.whatsapp',
        'lead_status' => 'lead.status',
        'lead_prioridade' => 'lead.priority',
        'lead_temperatura' => 'lead.temperature',
        'lead_score' => 'lead.score',
        'lead_observacoes' => 'lead.notes',
    ];

    /** @return array<string, array{label: string, fields: array<string, string>}> */
    public function groups(): array
    {
        return self::GROUPS;
    }

    /** @return list<string> */
    public function targets(): array
    {
        return array_merge(...array_values(array_map(fn (array $group): array => array_keys($group['fields']), self::GROUPS)));
    }

    public function allows(string $target): bool
    {
        return in_array($target, $this->targets(), true);
    }

    public function suggest(string $header): ?string
    {
        if (isset(self::OFFICIAL_TEMPLATE_HEADERS[$header])) {
            return self::OFFICIAL_TEMPLATE_HEADERS[$header];
        }

        return self::SUGGESTIONS[$this->normalizeHeader($header)] ?? null;
    }

    private function normalizeHeader(string $header): string
    {
        $header = Str::lower(Str::ascii(trim($header)));
        $header = preg_replace('/[^a-z0-9]+/', ' ', $header) ?? '';

        return trim(preg_replace('/\s+/', ' ', $header) ?? '');
    }
}
