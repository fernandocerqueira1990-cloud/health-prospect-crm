<?php

namespace App\Actions\Imports;

use App\Models\DataImport;
use App\Models\ImportRow;
use App\Models\User;
use App\Services\AuditService;
use App\Services\ImportIntegrityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplyConservativeImportDedupPolicyAction
{
    public function __construct(private readonly AuditService $audit, private readonly ImportIntegrityService $integrity) {}

    /** @return array{rows_reused: int, rows_ignored: int, rows_new: int, rows_blocked: int} */
    public function execute(DataImport $dataImport, User $user): array
    {
        return DB::transaction(function () use ($dataImport, $user): array {
            $lockedImport = DataImport::query()->lockForUpdate()->findOrFail($dataImport->id);
            if ($lockedImport->status !== DataImport::STATUS_PARSED || ! is_array($lockedImport->metadata['dedup'] ?? null)) {
                throw ValidationException::withMessages(['dedup' => 'Analise as duplicidades antes de aplicar a regra automática.']);
            }

            $result = ['rows_reused' => 0, 'rows_ignored' => 0, 'rows_new' => 0, 'rows_blocked' => 0];
            ImportRow::query()->where('import_id', $lockedImport->id)->orderBy('id')->lockForUpdate()->chunkById(500, function ($rows) use (&$result): void {
                foreach ($rows as $row) {
                    $dedup = $row->dedup_data;
                    if (! is_array($dedup) || ($dedup['version'] ?? null) !== 1 || ! is_array($dedup['groups'] ?? null)) {
                        continue;
                    }
                    if (($dedup['status'] ?? null) === 'blocked') {
                        $result['rows_blocked']++;

                        continue;
                    }
                    $reused = false;
                    $ignored = false;

                    foreach ($dedup['groups'] as $group => $groupData) {
                        $candidates = $groupData['candidates'] ?? [];
                        $candidate = $this->exactCandidate($candidates);

                        if ($candidate !== null) {
                            $source = $candidate['source'];
                            $candidateId = (int) ($source === 'crm' ? $candidate['id'] : $candidate['import_row_id']);

                            $dedup['groups'][$group]['decision'] = [
                                'action' => $source === 'crm' ? 'use_existing' : 'reuse_import_row',
                                'candidate_source' => $source,
                                'candidate_id' => $candidateId,
                            ];
                            $reused = true;

                            continue;
                        }

                        $hasArchivedExactCandidate = collect($candidates)->contains(
                            fn (array $candidate): bool => ($candidate['strength'] ?? null) === 'exact'
                                && $candidate['source'] === 'crm'
                                && ($candidate['archived'] ?? false)
                        );

                        if (($groupData['match'] ?? null) === 'possible' || $hasArchivedExactCandidate) {
                            $dedup['groups'][$group]['decision'] = [
                                'action' => 'skip',
                                'candidate_source' => null,
                                'candidate_id' => null,
                            ];
                            $ignored = true;

                            continue;
                        }

                        $dedup['groups'][$group]['decision'] = [
                            'action' => 'create_new',
                            'candidate_source' => null,
                            'candidate_id' => null,
                        ];
                    }

                    $dedup['status'] = 'resolved';
                    $row->dedup_data = $dedup;
                    $row->save();

                    if ($reused) {
                        $result['rows_reused']++;
                    } elseif ($ignored) {
                        $result['rows_ignored']++;
                    } else {
                        $result['rows_new']++;
                    }
                }
            });

            $this->refreshSummary($lockedImport);
            if (($lockedImport->refresh()->metadata['security']['version'] ?? null) === 1) {
                $metadata = $lockedImport->metadata;
                $metadata['security']['dedup_signature'] = $this->integrity->dedupSignature($lockedImport);
                $lockedImport->update(['metadata' => $metadata]);
            }
            $this->audit->record('import_dedup_conservative_policy_applied', $lockedImport, after: $result, user: $user);

            return $result;
        });
    }

    /** @param list<array<string, mixed>> $candidates @return array<string, mixed>|null */
    private function exactCandidate(array $candidates): ?array
    {
        foreach ($candidates as $candidate) {
            if (($candidate['strength'] ?? null) === 'exact' && in_array($candidate['source'] ?? null, ['crm', 'import'], true) && ! ($candidate['source'] === 'crm' && ($candidate['archived'] ?? false))) {
                return $candidate;
            }
        }

        return null;
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
