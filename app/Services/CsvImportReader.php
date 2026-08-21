<?php

namespace App\Services;

use App\Exceptions\CsvImportException;
use App\Models\DataImport;
use App\Models\ImportRow;
use App\Support\ImportStoragePath;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use SplFileObject;
use Throwable;

class CsvImportReader
{
    public function __construct(private readonly AuditService $audit) {}

    public function parse(DataImport $dataImport): DataImport
    {
        $dataImport->update(['status' => DataImport::STATUS_PROCESSING, 'started_at' => now(), 'finished_at' => null]);

        try {
            DB::transaction(function () use ($dataImport): void {
                $dataImport->rows()->delete();
                $result = $this->read($dataImport);

                $dataImport->update([
                    'status' => DataImport::STATUS_PARSED,
                    'total_rows' => $result['total_rows'],
                    'finished_at' => now(),
                    'metadata' => ['delimiter' => $this->delimiterName($result['delimiter']), 'header' => $result['header'], 'security' => ['version' => 1]],
                ]);
            });

            return $dataImport->refresh();
        } catch (Throwable $exception) {
            report($exception);
            $reason = $exception instanceof CsvImportException
                ? $exception->reason
                : 'csv_parse_error';
            $dataImport->update([
                'status' => DataImport::STATUS_FAILED,
                'failed_rows' => 0,
                'finished_at' => now(),
                'metadata' => [
                    'error' => 'Não foi possível interpretar o arquivo CSV.',
                    'error_code' => $reason,
                ],
            ]);
            $this->audit->record('import_failed', $dataImport, after: ['status' => DataImport::STATUS_FAILED, 'reason' => $reason]);

            return $dataImport->refresh();
        }
    }

    /** @return array{delimiter: string, header: list<string>, total_rows: int} */
    private function read(DataImport $dataImport): array
    {
        $diskName = (string) config('imports.disk');
        if (config('imports.requires_local_disk') && config("filesystems.disks.{$diskName}.driver") !== 'local') {
            throw new CsvImportException('local_disk_required');
        }

        $path = Storage::disk($diskName)->path(ImportStoragePath::assertSafe($dataImport));
        if (is_link($path)) {
            throw new CsvImportException('unsafe_storage_path');
        }
        $delimiter = $this->detectDelimiter($path);
        $file = new SplFileObject($path, 'rb');
        $header = null;
        $batch = [];
        $totalRows = 0;
        $batchSize = max(1, (int) config('imports.batch_size'));
        $physicalRow = 0;
        $recordStart = null;
        $recordBuffer = '';
        $recordHasOpenQuote = false;
        $timestamp = now();
        $maxRecordBytes = max(1024, (int) config('imports.csv_max_record_bytes'));
        $maxColumns = max(1, (int) config('imports.csv_max_columns'));
        $maxRows = max(1, (int) config('imports.csv_max_rows'));
        $maxHeaderLength = max(1, (int) config('imports.header_max_length'));

        while (! $file->eof()) {
            $line = $file->fgets();
            $physicalRow++;

            if (! mb_check_encoding($line, 'UTF-8')) {
                throw new CsvImportException('invalid_encoding');
            }

            if ($recordStart === null && trim($line) === '') {
                continue;
            }

            $recordStart ??= $physicalRow;
            if (strlen($recordBuffer) + strlen($line) > $maxRecordBytes) {
                throw new CsvImportException('record_too_large');
            }
            $recordBuffer .= $line;
            $this->updateQuoteState($line, $recordHasOpenQuote);

            if ($recordHasOpenQuote) {
                continue;
            }

            $record = $this->parseStrictRecord($recordBuffer, $delimiter);
            if ($record === null) {
                throw new CsvImportException('malformed_csv');
            }

            $recordBuffer = '';
            $currentRow = $recordStart;
            $recordStart = null;

            if ($header === null) {
                $record[0] = preg_replace('/^\xEF\xBB\xBF/', '', $record[0]) ?? $record[0];
                $header = array_map('trim', $record);
                $normalizedHeader = array_map(fn (string $value): string => mb_strtolower($value), $header);
                if (count($header) > $maxColumns || in_array('', $header, true) || count($normalizedHeader) !== count(array_unique($normalizedHeader))
                    || array_any($header, fn (string $value): bool => mb_strlen($value) > $maxHeaderLength || preg_match('/[\p{Cc}\p{Cf}]/u', $value) === 1 || preg_match('/^[=+\-@]/', $value) === 1)) {
                    throw new CsvImportException('invalid_header');
                }

                continue;
            }

            if ($this->isEmpty($record)) {
                continue;
            }
            if (count($record) !== count($header)) {
                throw new CsvImportException('column_count_mismatch');
            }
            $original = array_combine($header, $record);
            $batch[] = ['import_id' => $dataImport->id, 'row_number' => $currentRow, 'status' => ImportRow::STATUS_PARSED, 'original_data' => json_encode($original, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), 'normalized_data' => null, 'error_message' => null, 'related_entity_type' => null, 'related_entity_id' => null, 'created_at' => $timestamp, 'updated_at' => $timestamp];
            $totalRows++;
            if ($totalRows > $maxRows) {
                throw new CsvImportException('row_limit_exceeded');
            }
            if (count($batch) >= $batchSize) {
                ImportRow::query()->insert($batch);
                $batch = [];
            }
        }

        if ($recordBuffer !== '') {
            throw new CsvImportException('malformed_csv');
        }

        if ($header === null) {
            throw new CsvImportException('invalid_header');
        }

        if ($batch !== []) {
            ImportRow::query()->insert($batch);
        }

        return ['delimiter' => $delimiter, 'header' => $header, 'total_rows' => $totalRows];
    }

    private function detectDelimiter(string $path): string
    {
        $file = new SplFileObject($path, 'rb');
        $line = '';
        while (! $file->eof() && trim($line) === '') {
            $line = (string) $file->fgets();
        }
        if ($line === '' || ! mb_check_encoding($line, 'UTF-8')) {
            throw new CsvImportException('invalid_header');
        }
        $scores = [];
        foreach ([',', ';', "\t"] as $delimiter) {
            try {
                $record = $this->parseStrictRecord($line, $delimiter);
                $scores[$delimiter] = $record === null ? 0 : count($record);
            } catch (CsvImportException) {
                $scores[$delimiter] = 0;
            }
        }
        $best = max($scores);
        $winners = array_keys($scores, $best, true);
        if ($best < 2 || count($winners) !== 1) {
            throw new CsvImportException('invalid_header');
        }

        return $winners[0];
    }

    /** @param array<int, mixed> $record */
    private function isEmpty(array $record): bool
    {
        return count(array_filter($record, fn (mixed $value): bool => trim((string) $value) !== '')) === 0;
    }

    private function delimiterName(string $delimiter): string
    {
        return $delimiter === "\t" ? 'tab' : $delimiter;
    }

    private function updateQuoteState(string $line, bool &$hasOpenQuote): void
    {
        $length = strlen($line);

        for ($index = 0; $index < $length; $index++) {
            if ($line[$index] !== '"') {
                continue;
            }

            if ($hasOpenQuote && $index + 1 < $length && $line[$index + 1] === '"') {
                $index++;

                continue;
            }

            $hasOpenQuote = ! $hasOpenQuote;
        }
    }

    /** @return list<string>|null */
    private function parseStrictRecord(string $record, string $delimiter): ?array
    {
        $fields = [];
        $field = '';
        $state = 'start';
        $length = strlen($record);

        for ($index = 0; $index < $length; $index++) {
            $character = $record[$index];

            if ($state === 'quoted') {
                if ($character !== '"') {
                    $field .= $character;

                    continue;
                }

                if ($index + 1 < $length && $record[$index + 1] === '"') {
                    $field .= '"';
                    $index++;

                    continue;
                }

                $state = 'after_quote';

                continue;
            }

            if ($character === "\r" || $character === "\n") {
                if (trim(substr($record, $index), "\r\n") !== '') {
                    throw new CsvImportException('malformed_csv');
                }

                break;
            }

            if ($state === 'after_quote') {
                if ($character !== $delimiter) {
                    throw new CsvImportException('malformed_csv');
                }

                $fields[] = $field;
                $field = '';
                $state = 'start';

                continue;
            }

            if ($state === 'start' && $character === '"') {
                $state = 'quoted';

                continue;
            }

            if ($character === '"') {
                throw new CsvImportException('malformed_csv');
            }

            if ($character === $delimiter) {
                $fields[] = $field;
                $field = '';
                $state = 'start';

                continue;
            }

            $field .= $character;
            $state = 'unquoted';
        }

        if ($state === 'quoted') {
            return null;
        }

        $fields[] = $field;

        return $fields;
    }
}
