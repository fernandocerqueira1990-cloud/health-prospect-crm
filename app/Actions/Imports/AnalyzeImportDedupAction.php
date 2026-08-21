<?php

namespace App\Actions\Imports;

use App\Models\DataImport;
use App\Models\ImportRow;
use App\Models\User;
use App\Services\AuditService;
use App\Services\ImportDedupKeyRegistry;
use App\Services\ImportDedupMatcher;
use App\Services\ImportIntegrityService;
use App\Services\ImportPreviewService;
use App\Services\ImportPreviewValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AnalyzeImportDedupAction
{
    public function __construct(
        private readonly ImportPreviewService $preview,
        private readonly ImportPreviewValidator $validator,
        private readonly ImportDedupMatcher $matcher,
        private readonly AuditService $audit,
        private readonly ImportIntegrityService $integrity,
    ) {}

    public function execute(DataImport $dataImport, User $user): DataImport
    {
        return DB::transaction(function () use ($dataImport, $user): DataImport {
            $lockedImport = DataImport::query()->lockForUpdate()->findOrFail($dataImport->id);
            if ($lockedImport->status !== DataImport::STATUS_PARSED || ! $this->preview->hasValidMapping($lockedImport) || ! $this->preview->hasNormalizedData($lockedImport)) {
                throw ValidationException::withMessages(['dedup' => 'Mapeie e valide os dados antes de analisar duplicidades.']);
            }

            $mappedTargets = array_values($lockedImport->metadata['mapping']['columns']);
            $registry = new ImportDedupKeyRegistry;
            $summary = ['total' => 0, 'clear' => 0, 'review' => 0, 'resolved' => 0, 'blocked' => 0, 'exact_matches' => 0, 'possible_matches' => 0];

            ImportRow::query()->where('import_id', $lockedImport->id)->orderBy('id')->chunkById(500, function ($rows) use ($registry, &$summary, $mappedTargets): void {
                $results = $this->matcher->match($rows, $registry, $mappedTargets, $this->validator);
                $updates = [];
                foreach ($rows as $row) {
                    $dedup = $results[$row->id];
                    $updates[] = [
                        'id' => $row->id,
                        'import_id' => $row->import_id,
                        'row_number' => $row->row_number,
                        'status' => $row->status,
                        'original_data' => json_encode($row->original_data, JSON_THROW_ON_ERROR),
                        'normalized_data' => json_encode($row->normalized_data, JSON_THROW_ON_ERROR),
                        'dedup_data' => json_encode($dedup, JSON_THROW_ON_ERROR),
                        'created_at' => $row->created_at,
                        'updated_at' => now(),
                    ];
                    $summary['total']++;
                    $summary[$dedup['status']]++;
                    $hasExact = array_any($dedup['groups'], fn (array $group): bool => $group['match'] === 'exact');
                    $hasPossible = array_any($dedup['groups'], fn (array $group): bool => $group['match'] === 'possible');
                    if ($hasExact) {
                        $summary['exact_matches']++;
                    }
                    if ($hasPossible) {
                        $summary['possible_matches']++;
                    }
                }
                ImportRow::query()->upsert($updates, ['id'], ['dedup_data', 'updated_at']);
            });

            $analyzedAt = now()->toIso8601String();
            $metadata = $lockedImport->metadata;
            $metadata['dedup'] = ['version' => 1, 'analyzed_at' => $analyzedAt, 'analyzed_by_user_id' => $user->id, 'summary' => $summary];
            $lockedImport->update(['metadata' => $metadata, 'duplicate_rows' => $summary['exact_matches']]);
            if (($metadata['security']['version'] ?? null) === 1) {
                $metadata = $lockedImport->metadata;
                $metadata['security']['dedup_signature'] = $this->integrity->dedupSignature($lockedImport);
                $lockedImport->update(['metadata' => $metadata]);
            }
            $this->audit->record('import_dedup_analyzed', $lockedImport, after: ['import_id' => $lockedImport->id, 'summary' => $summary], user: $user);

            return $lockedImport->refresh();
        });
    }
}
