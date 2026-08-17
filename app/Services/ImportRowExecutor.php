<?php

namespace App\Services;

use App\Actions\Companies\CreateCompanyAction;
use App\Actions\Contacts\CreateContactAction;
use App\Actions\Leads\CreateLeadAction;
use App\Exceptions\ImportExecutionException;
use App\Models\Channel;
use App\Models\Company;
use App\Models\Contact;
use App\Models\DataImport;
use App\Models\ImportRow;
use App\Models\LeadSource;

class ImportRowExecutor
{
    private const COMPANY_FIELDS = ['legal_name', 'trade_name', 'tax_id', 'tax_id_country', 'segment', 'category', 'website', 'phone', 'email', 'street', 'number', 'complement', 'district', 'city', 'state', 'postal_code', 'employee_count_estimate', 'priority', 'notes'];

    private const CONTACT_FIELDS = ['name', 'job_title', 'department', 'email', 'phone', 'whatsapp', 'linkedin_url', 'decision_role', 'influence_level', 'notes'];

    private const LEAD_FIELDS = ['name', 'company_name', 'job_title', 'email', 'phone', 'whatsapp', 'status', 'priority', 'temperature', 'score', 'notes'];

    public function __construct(
        private readonly CreateCompanyAction $createCompany,
        private readonly CreateContactAction $createContact,
        private readonly CreateLeadAction $createLead,
    ) {}

    /** @return array{version: int, executed_at: string, status: string, groups: array<string, array<string, mixed>>} */
    public function execute(DataImport $dataImport, ImportRow $row, ImportExecutionEntityRegistry $registry, ImportExecutionCandidateRegistry $candidates, ?LeadSource $leadSource, ?Channel $channel): array
    {
        $dedup = $row->dedup_data;
        $normalized = $row->normalized_data;
        if (! is_array($dedup) || ! is_array($normalized)) {
            throw new ImportExecutionException('execution_data_invalid', 'A linha não possui dados válidos para execução.');
        }
        if (($dedup['status'] ?? null) === 'blocked') {
            return ['version' => 1, 'executed_at' => now()->toIso8601String(), 'status' => 'blocked', 'groups' => []];
        }

        $results = [];
        $resolved = [];
        foreach (['company', 'contact', 'lead'] as $group) {
            $groupData = $dedup['groups'][$group] ?? null;
            if (! is_array($groupData)) {
                continue;
            }
            $decision = $groupData['decision'];
            $action = $decision['action'];
            if ($action === 'skip') {
                $results[$group] = ['action' => 'skip', 'result' => 'skipped'];

                continue;
            }

            $entityId = match ($action) {
                'create_new' => $this->create($group, $normalized[$group] ?? [], $resolved, $dataImport, $row, $leadSource, $channel),
                'use_existing' => $this->existing($group, $groupData, $decision, $resolved, $candidates),
                'reuse_import_row' => $this->reused($group, $groupData, $decision, $registry),
                default => throw new ImportExecutionException('invalid_decision', 'A linha possui uma decisão inválida.'),
            };
            $resolved[$group] = $entityId;
            if ($group === 'contact' && ! isset($resolved['company'])) {
                $contactCompanyId = Contact::query()->whereKey($entityId)->value('company_id');
                if (is_int($contactCompanyId)) {
                    $resolved['company'] = $contactCompanyId;
                }
            }
            $results[$group] = ['action' => $action, 'result' => $action === 'create_new' ? 'created' : 'reused', 'entity_id' => $entityId];
        }

        $status = array_any($results, fn (array $result): bool => $result['result'] === 'created') ? 'success'
            : (array_any($results, fn (array $result): bool => $result['result'] === 'reused') ? 'reused' : 'skipped');

        return ['version' => 1, 'executed_at' => now()->toIso8601String(), 'status' => $status, 'groups' => $results];
    }

    /** @param array<string, mixed> $values @param array<string, int> $resolved */
    private function create(string $group, array $values, array $resolved, DataImport $dataImport, ImportRow $row, ?LeadSource $leadSource, ?Channel $channel): int
    {
        $audit = ['origin' => 'import', 'import_id' => $dataImport->id, 'row_number' => $row->row_number, 'group' => $group];

        if ($group === 'company') {
            $data = $this->only($values, self::COMPANY_FIELDS);
            $country = $data['tax_id_country'] ?? null;
            $taxId = $data['tax_id'] ?? null;
            if (is_string($country) && is_string($taxId) && Company::withTrashed()->where('tax_id_country', $country)->where('tax_id', $taxId)->exists()) {
                throw new ImportExecutionException('strong_duplicate_changed', 'A identidade fiscal passou a corresponder a uma empresa existente.');
            }

            return $this->createCompany->execute($data, $audit)->id;
        }
        if ($group === 'contact') {
            $companyId = $resolved['company'] ?? null;
            if ($companyId === null || ! Company::query()->whereKey($companyId)->exists()) {
                throw new ImportExecutionException('missing_company_dependency', 'O contato não possui uma empresa ativa resolvida.');
            }
            $data = $this->only($values, self::CONTACT_FIELDS) + ['company_id' => $companyId, 'is_primary' => false, 'active' => true];

            return $this->createContact->execute($data, $audit)->id;
        }
        if ($leadSource === null || $channel === null) {
            throw new ImportExecutionException('lead_acquisition_unavailable', 'Origem e canal ativos são obrigatórios para criar Leads.');
        }
        $activeSource = LeadSource::query()->whereKey($leadSource->id)->where('slug', 'importacao')->where('active', true)->first();
        $activeChannel = Channel::query()->whereKey($channel->id)->where('active', true)->first();
        if ($activeSource === null || $activeChannel === null) {
            throw new ImportExecutionException('lead_acquisition_changed', 'A origem ou o canal deixou de estar disponível durante a execução.');
        }
        $data = $this->only($values, self::LEAD_FIELDS) + [
            'company_id' => $resolved['company'] ?? null,
            'contact_id' => $resolved['contact'] ?? null,
            'source_id' => $activeSource->id,
            'channel_id' => $activeChannel->id,
        ];

        return $this->createLead->execute($data, $audit)->id;
    }

    /** @param array<string, int> $resolved */
    private function existing(string $group, array $groupData, array $decision, array $resolved, ImportExecutionCandidateRegistry $candidates): int
    {
        $candidateId = $this->validatedCandidateId($group, $groupData, $decision, 'crm');
        $model = $candidates->get($group, $candidateId);
        if ($model === null) {
            throw new ImportExecutionException('invalid_existing_candidate', 'O registro selecionado não está mais disponível.');
        }
        if ($model->trashed()) {
            throw new ImportExecutionException('archived_existing_candidate', 'O registro selecionado está arquivado e não pode ser reutilizado.');
        }
        if ($model instanceof Contact && isset($resolved['company']) && $model->company_id !== $resolved['company']) {
            throw new ImportExecutionException('contact_company_mismatch', 'O contato selecionado não pertence à empresa resolvida.');
        }

        return $model->id;
    }

    private function reused(string $group, array $groupData, array $decision, ImportExecutionEntityRegistry $registry): int
    {
        $rowId = $this->validatedCandidateId($group, $groupData, $decision, 'import');
        $entityId = $registry->get($rowId, $group);
        if ($entityId === null) {
            throw new ImportExecutionException('invalid_import_dependency', 'A entidade da linha anterior não foi resolvida.');
        }

        return $entityId;
    }

    private function validatedCandidateId(string $group, array $groupData, array $decision, string $source): int
    {
        $id = $decision['candidate_id'] ?? null;
        if (($decision['candidate_source'] ?? null) !== $source || ! is_int($id) || $id < 1) {
            throw new ImportExecutionException('invalid_existing_candidate', 'A decisão possui uma referência inválida.');
        }
        foreach (($groupData['candidates'] ?? []) as $candidate) {
            if (is_array($candidate) && ($candidate['entity'] ?? null) === $group && ($candidate['source'] ?? null) === $source && (int) ($candidate[$source === 'crm' ? 'id' : 'import_row_id'] ?? 0) === $id) {
                return $id;
            }
        }

        throw new ImportExecutionException('invalid_existing_candidate', 'O candidato não pertence à análise da linha.');
    }

    /** @param array<string, mixed> $values @param list<string> $fields @return array<string, mixed> */
    private function only(array $values, array $fields): array
    {
        return array_intersect_key($values, array_flip($fields));
    }
}
