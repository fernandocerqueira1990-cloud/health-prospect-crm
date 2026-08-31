<?php

namespace App\Services;

use App\Exceptions\ImportExecutionException;
use App\Models\DataImport;

class ImportExecutionPrerequisites
{
    private const ACTIONS = ['create_new', 'use_existing', 'reuse_import_row', 'skip'];

    public function __construct(
        private readonly ImportPreviewService $preview,
        private readonly ImportValueNormalizer $normalizer,
        private readonly ImportPreviewValidator $validator,
        private readonly ImportIntegrityService $integrity,
    ) {}

    public function validate(DataImport $dataImport): void
    {
        $resumingFailedExecution = $dataImport->status === DataImport::STATUS_FAILED
            && ($dataImport->metadata['execution']['status'] ?? null) === 'failed';
        if ($dataImport->status !== DataImport::STATUS_PARSED && ! $resumingFailedExecution) {
            throw new ImportExecutionException('invalid_import_status', 'A importação não está pronta para execução.');
        }
        if (! $this->preview->hasValidMapping($dataImport) || ! $this->preview->hasNormalizedData($dataImport)) {
            throw new ImportExecutionException('mapping_required', 'Mapeie e valide os dados antes da execução final.');
        }
        if (($dataImport->metadata['dedup']['version'] ?? null) !== 1 || ! isset($dataImport->metadata['dedup']['analyzed_at'])) {
            throw new ImportExecutionException('dedup_required', 'Analise e resolva as duplicidades antes da execução final.');
        }

        if (($dataImport->metadata['security']['version'] ?? null) === 1 && ! $this->integrity->validDedupSignature($dataImport)) {
            throw new ImportExecutionException('import_data_tampered', 'Os dados da importação foram alterados após a análise. Execute novamente o mapeamento e a deduplicação.');
        }
        $seenRows = [];
        $mapping = $dataImport->metadata['mapping']['columns'];
        $mappedTargets = array_values($mapping);
        foreach ($dataImport->rows()->select(['id', 'row_number', 'original_data', 'normalized_data', 'dedup_data', 'execution_data'])->lazyById(500, 'row_number') as $row) {
            if (($dataImport->metadata['security']['version'] ?? null) === 1) {
                if ($row->execution_data !== null && ! $this->integrity->validExecutionSignature($dataImport, $row)) {
                    throw new ImportExecutionException('execution_replay_data', 'A importação contém dados de execução inesperados.');
                }
                $expected = $this->normalizer->normalize($row->original_data, $mapping);
                if (! $this->normalizedDataMatches($row->normalized_data, $expected) || $this->validator->validate($expected, $mappedTargets)['status'] === ImportPreviewValidator::STATUS_ERROR) {
                    throw new ImportExecutionException('normalized_data_invalid', 'Os dados normalizados não correspondem ao arquivo e ao mapeamento aprovados.');
                }
            }
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

    private function normalizedDataMatches(mixed $actual, mixed $expected): bool
    {
        return $this->canonicalizeNormalizedData($actual)
            === $this->canonicalizeNormalizedData($expected);
    }

    private function canonicalizeNormalizedData(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalizeNormalizedData($item);
        }

        if (! array_is_list($value)) {
            ksort($value);
        }

        return $value;
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
