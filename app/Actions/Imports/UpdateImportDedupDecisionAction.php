<?php

namespace App\Actions\Imports;

use App\Models\Company;
use App\Models\Contact;
use App\Models\DataImport;
use App\Models\ImportRow;
use App\Models\Lead;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateImportDedupDecisionAction
{
    public function __construct(private readonly AuditService $audit) {}

    public function execute(DataImport $dataImport, ImportRow $importRow, string $group, string $action, ?string $candidateRef, User $user): ImportRow
    {
        return DB::transaction(function () use ($dataImport, $importRow, $group, $action, $candidateRef, $user): ImportRow {
            $lockedImport = DataImport::query()->lockForUpdate()->findOrFail($dataImport->id);
            $row = ImportRow::query()->lockForUpdate()->where('import_id', $lockedImport->id)->findOrFail($importRow->id);
            $dedup = $row->dedup_data;
            if (! is_array($dedup) || ($dedup['version'] ?? null) !== 1 || ! isset($dedup['groups'][$group])) {
                throw ValidationException::withMessages(['group' => 'O grupo não possui análise de deduplicação válida.']);
            }
            if (($dedup['status'] ?? null) === 'blocked') {
                throw ValidationException::withMessages(['action' => 'Linhas bloqueadas devem ser corrigidas no mapeamento antes da decisão.']);
            }

            $groupData = $dedup['groups'][$group];
            $candidate = null;
            $source = null;
            $candidateId = null;
            if (in_array($action, ['use_existing', 'reuse_import_row'], true)) {
                $expectedSource = $action === 'use_existing' ? 'crm' : 'import';
                [$source, $candidateEntity, $candidateId] = $this->decodeCandidateRef($candidateRef);
                if ($source !== $expectedSource || $candidateEntity !== $group) {
                    throw ValidationException::withMessages(['candidate_id' => 'Selecione um candidato válido para esta decisão.']);
                }
                $candidate = collect($groupData['candidates'])->first(fn (array $item): bool => $item['source'] === $source && (int) ($item[$source === 'crm' ? 'id' : 'import_row_id'] ?? 0) === $candidateId && $item['entity'] === $group);
                if (! is_array($candidate)) {
                    throw ValidationException::withMessages(['candidate_id' => 'O candidato não pertence à análise desta linha.']);
                }
                $this->assertCandidateExists($lockedImport, $group, $source, $candidateId);
            } elseif ($candidateRef !== null) {
                throw ValidationException::withMessages(['candidate_id' => 'Esta decisão não aceita candidato.']);
            }

            if ($action === 'create_new' && $group === 'company' && collect($groupData['candidates'])->contains(fn (array $item): bool => $item['strength'] === 'exact' && in_array('tax_id_country_tax_id', $item['reasons'], true))) {
                throw ValidationException::withMessages(['action' => 'Não é possível criar outra empresa com a mesma identidade fiscal.']);
            }

            $dedup['groups'][$group]['decision'] = ['action' => $action, 'candidate_source' => $source, 'candidate_id' => $candidateId];
            $dedup['status'] = collect($dedup['groups'])->contains(fn (array $item): bool => ($item['decision']['action'] ?? 'pending') === 'pending') ? 'review' : 'resolved';
            $row->dedup_data = $dedup;
            $row->save();
            $this->refreshSummary($lockedImport);
            $this->audit->record('import_dedup_decision_updated', $row, after: ['import_id' => $lockedImport->id, 'row_number' => $row->row_number, 'group' => $group, 'action' => $action, 'candidate_source' => $source, 'candidate_id' => $candidateId], user: $user);

            return $row->refresh();
        });
    }

    /** @return array{string, string, int} */
    private function decodeCandidateRef(?string $reference): array
    {
        try {
            $decoded = json_decode(Crypt::decryptString((string) $reference), true, flags: JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException) {
            throw ValidationException::withMessages(['candidate_id' => 'A referência do candidato é inválida.']);
        }
        if (! is_array($decoded) || ! is_string($decoded['source'] ?? null) || ! is_string($decoded['entity'] ?? null) || ! is_int($decoded['id'] ?? null) || $decoded['id'] < 1) {
            throw ValidationException::withMessages(['candidate_id' => 'A referência do candidato é inválida.']);
        }

        return [$decoded['source'], $decoded['entity'], $decoded['id']];
    }

    private function assertCandidateExists(DataImport $dataImport, string $group, string $source, int $candidateId): void
    {
        if ($source === 'import') {
            $exists = ImportRow::query()->where('import_id', $dataImport->id)->whereKey($candidateId)->exists();
        } else {
            $model = match ($group) {
                'company' => Company::class, 'contact' => Contact::class, 'lead' => Lead::class,
                default => throw new \LogicException('Unsupported dedup group.'),
            };
            $exists = $model::withTrashed()->whereKey($candidateId)->exists();
        }
        if (! $exists) {
            throw ValidationException::withMessages(['candidate_id' => 'O candidato selecionado não está mais disponível.']);
        }
    }

    private function refreshSummary(DataImport $dataImport): void
    {
        $summary = $dataImport->metadata['dedup']['summary'] ?? [];
        foreach (['clear', 'review', 'resolved', 'blocked'] as $status) {
            $summary[$status] = ImportRow::query()->where('import_id', $dataImport->id)->where('dedup_data->status', $status)->count();
        }
        $metadata = $dataImport->metadata;
        $metadata['dedup']['summary'] = $summary;
        $dataImport->update(['metadata' => $metadata]);
    }
}
