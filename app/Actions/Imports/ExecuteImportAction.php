<?php

namespace App\Actions\Imports;

use App\Exceptions\ImportExecutionException;
use App\Models\Channel;
use App\Models\DataImport;
use App\Models\ImportRow;
use App\Models\LeadSource;
use App\Models\User;
use App\Services\AuditService;
use App\Services\ImportExecutionCandidateRegistry;
use App\Services\ImportExecutionEntityRegistry;
use App\Services\ImportExecutionPrerequisites;
use App\Services\ImportIntegrityService;
use App\Services\ImportRowExecutor;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExecuteImportAction
{
    public function __construct(
        private readonly ImportExecutionPrerequisites $prerequisites,
        private readonly ImportRowExecutor $rowExecutor,
        private readonly AuditService $audit,
        private readonly ImportIntegrityService $integrity,
    ) {}

    public function execute(DataImport $dataImport, ?int $channelId, User $user): DataImport
    {
        $context = DB::transaction(function () use ($dataImport, $channelId, $user): array {
            $locked = DataImport::query()->lockForUpdate()->findOrFail($dataImport->id);
            if ($locked->status === DataImport::STATUS_COMPLETED) {
                return ['completed' => true, 'import' => $locked];
            }
            if ($locked->status === DataImport::STATUS_PROCESSING) {
                throw new ImportExecutionException('execution_in_progress', 'Esta importação já está em execução.');
            }

            $this->prerequisites->validate($locked);
            $needsLeadChannel = $this->prerequisites->needsLeadChannel($locked);
            $source = null;
            $channel = null;
            if ($needsLeadChannel) {
                $source = LeadSource::query()->where('slug', 'importacao')->where('active', true)->first();
                if ($source === null) {
                    throw new ImportExecutionException('import_source_unavailable', 'A origem obrigatória de importação não está disponível.');
                }
                $channel = $channelId === null ? null : Channel::query()->whereKey($channelId)->where('active', true)->first();
                if ($channel === null) {
                    throw new ImportExecutionException('lead_channel_required', 'Selecione um canal ativo para os novos Leads.');
                }
            }

            $startedAt = now();
            $metadata = $locked->metadata;
            $metadata['execution_config'] = [
                'version' => 1,
                'lead_source_slug' => 'importacao',
                'lead_channel_id' => $channel?->id,
            ];
            $metadata['execution'] = [
                'version' => 1,
                'started_at' => $startedAt->toIso8601String(),
                'started_by_user_id' => $user->id,
                'status' => 'processing',
            ];
            $locked->update(['status' => DataImport::STATUS_PROCESSING, 'started_at' => $startedAt, 'finished_at' => null, 'imported_rows' => 0, 'failed_rows' => 0, 'metadata' => $metadata]);
            $this->audit->record('import_execution_started', $locked, after: ['import_id' => $locked->id, 'lead_channel_id' => $channel?->id], user: $user);

            return ['completed' => false, 'import' => $locked, 'source' => $source, 'channel' => $channel];
        });

        if ($context['completed']) {
            return $context['import']->refresh();
        }

        /** @var DataImport $executingImport */
        $executingImport = $context['import'];
        /** @var LeadSource|null $source */
        $source = $context['source'];
        /** @var Channel|null $channel */
        $channel = $context['channel'];
        $summary = $this->emptySummary();
        $registry = new ImportExecutionEntityRegistry;
        $candidates = new ImportExecutionCandidateRegistry;

        try {
            $executingImport->rows()->select(['id', 'import_id', 'row_number', 'normalized_data', 'dedup_data', 'execution_data'])->chunkById(100, function ($rows) use ($executingImport, $registry, $candidates, $source, $channel, $user, &$summary): void {
                $candidates->load($rows);
                foreach ($rows as $row) {
                    if (is_array($row->execution_data) && ($row->execution_data['version'] ?? null) === 1) {
                        $this->rememberResults($registry, $row);
                        $this->accumulate($summary, $row->execution_data);

                        continue;
                    }

                    try {
                        $execution = DB::transaction(function () use ($executingImport, $row, $registry, $candidates, $source, $channel, $user): array {
                            $lockedRow = ImportRow::query()->lockForUpdate()->where('import_id', $executingImport->id)->findOrFail($row->id);
                            $result = $this->rowExecutor->execute($executingImport, $lockedRow, $registry, $candidates, $source, $channel);
                            $result['integrity_signature'] = $this->integrity->executionSignature($executingImport, $lockedRow, $result);
                            $lockedRow->execution_data = $result;
                            $lockedRow->save();
                            $this->audit->record('import_row_executed', $lockedRow, after: ['import_id' => $executingImport->id, 'row_number' => $lockedRow->row_number, 'status' => $result['status'], 'groups' => $result['groups']], user: $user);

                            return $result;
                        });
                    } catch (Throwable $exception) {
                        $error = $this->safeError($exception);
                        Log::warning('Import row execution failed.', ['import_id' => $executingImport->id, 'row_number' => $row->row_number, 'error_code' => $error->errorCode, 'exception_class' => $exception::class]);
                        $execution = DB::transaction(function () use ($executingImport, $row, $error, $user): array {
                            $lockedRow = ImportRow::query()->lockForUpdate()->where('import_id', $executingImport->id)->findOrFail($row->id);
                            $result = ['version' => 1, 'executed_at' => now()->toIso8601String(), 'status' => 'failed', 'error_code' => $error->errorCode, 'groups' => []];
                            $result['integrity_signature'] = $this->integrity->executionSignature($executingImport, $lockedRow, $result);
                            $lockedRow->execution_data = $result;
                            $lockedRow->save();
                            $this->audit->record('import_row_executed', $lockedRow, after: ['import_id' => $executingImport->id, 'row_number' => $lockedRow->row_number, 'status' => 'failed', 'error_code' => $error->errorCode], user: $user);

                            return $result;
                        });
                    }

                    $row->execution_data = $execution;
                    $this->rememberResults($registry, $row);
                    $this->accumulate($summary, $execution);
                }
            }, 'row_number');

            return DB::transaction(function () use ($executingImport, $summary, $user): DataImport {
                $locked = DataImport::query()->lockForUpdate()->findOrFail($executingImport->id);
                $finishedAt = now();
                $metadata = $locked->metadata;
                $metadata['execution'] = array_merge($metadata['execution'], ['status' => 'completed', 'finished_at' => $finishedAt->toIso8601String(), 'summary' => $summary]);
                $locked->update([
                    'status' => DataImport::STATUS_COMPLETED,
                    'imported_rows' => $summary['rows']['success'] + $summary['rows']['reused'],
                    'failed_rows' => $summary['rows']['failed'] + $summary['rows']['blocked'],
                    'finished_at' => $finishedAt,
                    'metadata' => $metadata,
                ]);
                $this->audit->record('import_execution_completed', $locked, after: ['import_id' => $locked->id, 'summary' => $summary], user: $user);

                return $locked->refresh();
            });
        } catch (Throwable $exception) {
            DB::transaction(function () use ($executingImport, $exception, $user): void {
                $locked = DataImport::query()->lockForUpdate()->findOrFail($executingImport->id);
                $metadata = $locked->metadata;
                $metadata['execution'] = array_merge($metadata['execution'] ?? [], ['status' => 'failed', 'finished_at' => now()->toIso8601String(), 'error_code' => 'execution_failed']);
                $locked->update(['status' => DataImport::STATUS_FAILED, 'finished_at' => now(), 'metadata' => $metadata]);
                $this->audit->record('import_execution_failed', $locked, after: ['import_id' => $locked->id, 'error_code' => 'execution_failed', 'exception_class' => $exception::class], user: $user);
            });
            Log::error('Import execution failed.', ['import_id' => $executingImport->id, 'exception_class' => $exception::class]);

            throw new ImportExecutionException('execution_failed', 'A execução da importação falhou de forma segura.');
        }
    }

    /** @return array{rows: array{success: int, reused: int, skipped: int, failed: int, blocked: int}, entities: array<string, array{created: int, reused: int, skipped: int}>} */
    private function emptySummary(): array
    {
        return ['rows' => ['success' => 0, 'reused' => 0, 'skipped' => 0, 'failed' => 0, 'blocked' => 0], 'entities' => ['company' => ['created' => 0, 'reused' => 0, 'skipped' => 0], 'contact' => ['created' => 0, 'reused' => 0, 'skipped' => 0], 'lead' => ['created' => 0, 'reused' => 0, 'skipped' => 0]]];
    }

    private function accumulate(array &$summary, array $execution): void
    {
        $summary['rows'][$execution['status']]++;
        foreach (($execution['groups'] ?? []) as $group => $result) {
            $bucket = $result['result'] === 'created' ? 'created' : ($result['result'] === 'skipped' ? 'skipped' : 'reused');
            $summary['entities'][$group][$bucket]++;
        }
    }

    private function rememberResults(ImportExecutionEntityRegistry $registry, ImportRow $row): void
    {
        foreach (($row->execution_data['groups'] ?? []) as $group => $result) {
            if (isset($result['entity_id']) && is_int($result['entity_id'])) {
                $registry->remember($row->id, $group, $result['entity_id']);
            }
        }
    }

    private function safeError(Throwable $exception): ImportExecutionException
    {
        if ($exception instanceof ImportExecutionException) {
            return $exception;
        }
        if ($exception instanceof QueryException && $exception->getCode() === '23505') {
            return new ImportExecutionException('constraint_conflict', 'Uma constraint de integridade impediu a execução da linha.');
        }

        return new ImportExecutionException('execution_failed', 'A linha não pôde ser executada.');
    }
}
