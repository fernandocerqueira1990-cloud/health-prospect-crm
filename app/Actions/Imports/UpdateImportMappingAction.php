<?php

namespace App\Actions\Imports;

use App\Models\DataImport;
use App\Models\ImportRow;
use App\Models\User;
use App\Services\AuditService;
use App\Services\ImportValueNormalizer;
use App\Support\ImportFieldCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateImportMappingAction
{
    public function __construct(
        private readonly ImportFieldCatalog $catalog,
        private readonly ImportValueNormalizer $normalizer,
        private readonly AuditService $audit,
    ) {}

    /** @param list<array{source: string, target?: string|null}> $columns */
    public function execute(DataImport $dataImport, array $columns, User $user): DataImport
    {
        return DB::transaction(function () use ($dataImport, $columns, $user): DataImport {
            $lockedImport = DataImport::query()->lockForUpdate()->findOrFail($dataImport->getKey());
            if ($lockedImport->status !== DataImport::STATUS_PARSED) {
                throw ValidationException::withMessages(['mapping' => 'Somente importações interpretadas podem ser mapeadas.']);
            }

            $headers = $this->headers($lockedImport);
            [$mapping, $ignored] = $this->validateMapping($columns, $headers);
            $previousMapping = $lockedImport->metadata['mapping']['columns'] ?? [];

            ImportRow::query()
                ->where('import_id', $lockedImport->id)
                ->select(['id', 'original_data'])
                ->chunkById(500, function ($rows) use ($mapping): void {
                    foreach ($rows as $row) {
                        $row->normalized_data = $this->normalizer->normalize($row->original_data, $mapping);
                        $row->dedup_data = null;
                        $row->execution_data = null;
                        $row->save();
                    }
                });

            $metadata = $lockedImport->metadata;
            unset($metadata['dedup']);
            unset($metadata['execution'], $metadata['execution_config']);
            $metadata['mapping'] = [
                'version' => 1,
                'mapped_at' => now()->toIso8601String(),
                'mapped_by_user_id' => $user->id,
                'columns' => $mapping,
                'ignored_columns' => $ignored,
            ];
            $lockedImport->update(['metadata' => $metadata, 'imported_rows' => 0, 'duplicate_rows' => 0, 'failed_rows' => 0, 'started_at' => null, 'finished_at' => null]);
            $this->audit->record('import_mapping_updated', $lockedImport, before: ['columns' => $previousMapping], after: ['columns' => $mapping, 'ignored_columns' => $ignored], user: $user);

            return $lockedImport->refresh();
        });
    }

    /** @return list<string> */
    private function headers(DataImport $dataImport): array
    {
        $headers = $dataImport->metadata['header'] ?? null;
        if (! is_array($headers) || array_filter($headers, 'is_string') !== $headers) {
            throw ValidationException::withMessages(['mapping' => 'A importação não possui um cabeçalho válido para mapeamento.']);
        }

        return array_values($headers);
    }

    /** @param list<array{source: string, target?: string|null}> $columns @param list<string> $headers @return array{array<string, string>, list<string>} */
    private function validateMapping(array $columns, array $headers): array
    {
        $mapping = [];
        $ignored = [];
        $seenSources = [];
        $seenTargets = [];

        foreach ($columns as $column) {
            $source = $column['source'];
            $target = $column['target'] ?? null;
            if (! in_array($source, $headers, true) || in_array($source, $seenSources, true)) {
                throw ValidationException::withMessages(['columns' => "A coluna de origem '{$source}' é inválida ou duplicada."]);
            }
            $seenSources[] = $source;

            if ($target === null || $target === '') {
                $ignored[] = $source;

                continue;
            }
            if (! $this->catalog->allows($target)) {
                throw ValidationException::withMessages(['columns' => "O destino '{$target}' não é permitido."]);
            }
            if (in_array($target, $seenTargets, true)) {
                throw ValidationException::withMessages(['columns' => "O destino '{$target}' não pode ser usado por mais de uma coluna."]);
            }
            $seenTargets[] = $target;
            $mapping[$source] = $target;
        }

        if (count($seenSources) !== count($headers) || array_diff($headers, $seenSources) !== []) {
            throw ValidationException::withMessages(['columns' => 'Todas as colunas do arquivo devem ser informadas, mesmo quando ignoradas.']);
        }

        return [$mapping, $ignored];
    }
}
