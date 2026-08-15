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
        return self::SUGGESTIONS[$this->normalizeHeader($header)] ?? null;
    }

    private function normalizeHeader(string $header): string
    {
        $header = Str::lower(Str::ascii(trim($header)));
        $header = preg_replace('/[^a-z0-9]+/', ' ', $header) ?? '';

        return trim(preg_replace('/\s+/', ' ', $header) ?? '');
    }
}
