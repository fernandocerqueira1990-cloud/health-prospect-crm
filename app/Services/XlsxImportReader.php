<?php

namespace App\Services;

use App\Exceptions\XlsxImportException;
use App\Models\DataImport;
use App\Models\ImportRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use Throwable;
use ZipArchive;

class XlsxImportReader
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
                    'metadata' => ['sheet' => $result['sheet'], 'header' => $result['header']],
                ]);
            });

            return $dataImport->refresh();
        } catch (Throwable $exception) {
            report($exception);
            $reason = $exception instanceof XlsxImportException ? $exception->reason : 'xlsx_parse_error';
            $dataImport->update([
                'status' => DataImport::STATUS_FAILED,
                'failed_rows' => 0,
                'finished_at' => now(),
                'metadata' => ['error' => 'Não foi possível interpretar o arquivo XLSX.', 'error_code' => $reason],
            ]);
            $this->audit->record('import_failed', $dataImport, after: ['status' => DataImport::STATUS_FAILED, 'reason' => $reason]);

            return $dataImport->refresh();
        }
    }

    /** @return array{sheet: string, header: list<string>, total_rows: int} */
    private function read(DataImport $dataImport): array
    {
        $diskName = (string) config('imports.disk');
        if (config('imports.requires_local_disk') && config("filesystems.disks.{$diskName}.driver") !== 'local') {
            throw new XlsxImportException('local_disk_required');
        }

        $path = Storage::disk($diskName)->path($dataImport->filename);
        $this->validateArchive($path);

        $reader = new Xlsx;
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        $worksheetInfo = $reader->listWorksheetInfo($path);
        $firstWorksheet = $worksheetInfo[0] ?? null;
        if ($firstWorksheet === null || $firstWorksheet['totalRows'] === 0 || $firstWorksheet['totalColumns'] === 0) {
            throw new XlsxImportException('invalid_header');
        }

        $maxRows = max(1, (int) config('imports.xlsx_max_rows'));
        $maxColumns = max(1, (int) config('imports.xlsx_max_columns'));
        if ($firstWorksheet['totalRows'] > $maxRows || $firstWorksheet['totalColumns'] > $maxColumns) {
            throw new XlsxImportException('worksheet_too_large');
        }

        $reader->setLoadSheetsOnly($firstWorksheet['worksheetName']);
        $reader->setReadFilter(new XlsxImportReadFilter($maxRows, $maxColumns));
        $spreadsheet = $reader->load($path);

        try {
            $sheet = $spreadsheet->getSheet(0);
            $highestRow = $sheet->getHighestDataRow();
            $highestColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
            if ($highestRow > $maxRows || $highestColumn > $maxColumns) {
                throw new XlsxImportException('worksheet_too_large');
            }

            $header = null;
            $batch = [];
            $totalRows = 0;
            $timestamp = now();
            $batchSize = max(1, (int) config('imports.batch_size'));

            for ($rowNumber = 1; $rowNumber <= $highestRow; $rowNumber++) {
                $values = [];
                for ($column = 1; $column <= $highestColumn; $column++) {
                    $values[] = $this->stagingValue($sheet->getCell([$column, $rowNumber])->getValue());
                }

                if ($this->isEmpty($values)) {
                    continue;
                }
                if ($header === null) {
                    $header = array_map(fn (mixed $value): string => $this->headerValue($value), $values);
                    $normalized = array_map(fn (string $value): string => mb_strtolower($value), $header);
                    if (in_array('', $header, true) || count($normalized) !== count(array_unique($normalized))) {
                        throw new XlsxImportException('invalid_header');
                    }

                    continue;
                }

                $original = array_combine($header, $values);
                $batch[] = ['import_id' => $dataImport->id, 'row_number' => $rowNumber, 'status' => ImportRow::STATUS_PARSED, 'original_data' => json_encode($original, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), 'normalized_data' => null, 'error_message' => null, 'related_entity_type' => null, 'related_entity_id' => null, 'created_at' => $timestamp, 'updated_at' => $timestamp];
                $totalRows++;
                if (count($batch) >= $batchSize) {
                    ImportRow::query()->insert($batch);
                    $batch = [];
                }
            }

            if ($header === null) {
                throw new XlsxImportException('invalid_header');
            }
            if ($batch !== []) {
                ImportRow::query()->insert($batch);
            }

            return ['sheet' => $sheet->getTitle(), 'header' => $header, 'total_rows' => $totalRows];
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    private function stagingValue(mixed $value): string|int|float|bool|null
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof RichText) {
            return $value->getPlainText();
        }
        if (is_scalar($value)) {
            return $value;
        }

        throw new XlsxImportException('unsupported_cell_value');
    }

    private function headerValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return trim((string) $value);
    }

    /** @param list<string|int|float|bool|null> $values */
    private function isEmpty(array $values): bool
    {
        return count(array_filter($values, fn (string|int|float|bool|null $value): bool => $value !== null && (! is_string($value) || trim($value) !== ''))) === 0;
    }

    private function validateArchive(string $path): void
    {
        $archive = new ZipArchive;
        if ($archive->open($path) !== true) {
            throw new XlsxImportException('invalid_archive');
        }

        try {
            $maxEntries = max(1, (int) config('imports.xlsx_max_archive_entries'));
            $maxUncompressedBytes = max(1, (int) config('imports.xlsx_max_uncompressed_bytes'));
            $maxCompressionRatio = max(1, (int) config('imports.xlsx_max_compression_ratio'));
            if ($archive->numFiles > $maxEntries) {
                throw new XlsxImportException('archive_too_large');
            }

            $uncompressedBytes = 0;
            for ($index = 0; $index < $archive->numFiles; $index++) {
                $entry = $archive->statIndex($index);
                if ($entry === false) {
                    throw new XlsxImportException('invalid_archive');
                }

                $size = (int) $entry['size'];
                $compressedSize = (int) $entry['comp_size'];
                $uncompressedBytes += $size;
                if ($uncompressedBytes > $maxUncompressedBytes || ($size > 0 && $compressedSize === 0) || ($compressedSize > 0 && $size / $compressedSize > $maxCompressionRatio)) {
                    throw new XlsxImportException('archive_too_large');
                }
            }
        } finally {
            $archive->close();
        }
    }
}
