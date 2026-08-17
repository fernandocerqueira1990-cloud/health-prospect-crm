<?php

namespace App\Services;

use App\Exceptions\ImportExecutionException;
use App\Models\DataImport;

class ImportExecutionPrerequisites
{
    private const ACTIONS = ['create_new', 'use_existing', 'reuse_import_row', 'skip'];

    public function __construct(private readonly ImportPreviewService $preview) {}

    public function validate(DataImport $dataImport): void
    {
        if ($dataImport->status !== DataImport::STATUS_PARSED) {
            throw new ImportExecutionException('invalid_import_status', 'A importação não está pronta para execução.');
        }
        if (! $this->preview->hasValidMapping($dataImport) || ! $this->preview->hasNormalizedData($dataImport)) {
            throw new ImportExecutionException('mapping_required', 'Mapeie e valide os dados antes da execução final.');
        }
        if (($dataImport->metadata['dedup']['version'] ?? null) !== 1 || ! isset($dataImport->metadata['dedup']['analyzed_at'])) {
            throw new ImportExecutionException('dedup_required', 'Analise e resolva as duplicidades antes da execução final.');
        }

        $seenRows = [];
        foreach ($dataImport->rows()->select(['id', 'row_number', 'dedup_data'])->lazyById(500, 'row_number') as $row) {
            $dedup = $row->dedup_data;
            if (! is_array($dedup) || ($dedup['version'] ?? null) !== 1 || ! isset($dedup['groups']) || ! is_array($dedup['groups'])) {
                throw new ImportExecutionException('dedup_stale', 'A análise de duplicidades está ausente ou desatualizada.');
            }
            if (($dedup['status'] ?? null) !== 'blocked') {
                foreach ($dedup['groups'] as $group => $groupData) {
                    $this->validateDecision($group, $groupData, $seenRows);
                }
            }
            $seenRows[$row->id] = $row->row_number;
        }
    }

    public function needsLeadChannel(DataImport $dataImport): bool
    {
        return $dataImport->rows()
            ->where('dedup_data->status', '!=', 'blocked')
            ->where('dedup_data->groups->lead->decision->action', 'create_new')
            ->exists();
    }

    /** @param array<int, int> $seenRows */
    private function validateDecision(mixed $group, mixed $groupData, array $seenRows): void
    {
        if (! is_string($group) || ! in_array($group, ['company', 'contact', 'lead'], true) || ! is_array($groupData)) {
            throw new ImportExecutionException('invalid_decision', 'A análise contém um grupo inválido.');
        }
        $decision = $groupData['decision'] ?? null;
        $action = is_array($decision) ? ($decision['action'] ?? null) : null;
        if (! is_string($action) || ! in_array($action, self::ACTIONS, true)) {
            throw new ImportExecutionException('pending_decision', 'Todas as decisões de deduplicação devem ser resolvidas antes da execução.');
        }
        if (! in_array($action, ['use_existing', 'reuse_import_row'], true)) {
            return;
        }
        $source = $decision['candidate_source'] ?? null;
        $candidateId = $decision['candidate_id'] ?? null;
        $expectedSource = $action === 'use_existing' ? 'crm' : 'import';
        if ($source !== $expectedSource || ! is_int($candidateId) || $candidateId < 1 || ! $this->candidateExists($group, $groupData, $source, $candidateId)) {
            throw new ImportExecutionException('invalid_existing_candidate', 'Uma decisão referencia um candidato inválido.');
        }
        if ($source === 'import' && ! isset($seenRows[$candidateId])) {
            throw new ImportExecutionException('invalid_import_dependency', 'Uma decisão referencia uma linha futura ou externa à importação.');
        }
    }

    private function candidateExists(string $group, array $groupData, string $source, int $candidateId): bool
    {
        foreach (($groupData['candidates'] ?? []) as $candidate) {
            if (is_array($candidate) && ($candidate['entity'] ?? null) === $group && ($candidate['source'] ?? null) === $source && (int) ($candidate[$source === 'crm' ? 'id' : 'import_row_id'] ?? 0) === $candidateId) {
                return true;
            }
        }

        return false;
    }
}
