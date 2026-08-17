<?php

namespace App\Services;

use App\Models\DataImport;
use App\Models\ImportRow;
use App\Support\ImportFieldCatalog;
use Illuminate\Pagination\LengthAwarePaginator;

class ImportPreviewService
{
    public function __construct(
        private readonly ImportPreviewValidator $validator,
        private readonly ImportFieldCatalog $catalog,
    ) {}

    public function hasValidMapping(DataImport $dataImport): bool
    {
        $mapping = $dataImport->metadata['mapping'] ?? null;
        $headers = $dataImport->metadata['header'] ?? null;
        if (! is_array($mapping) || ($mapping['version'] ?? null) !== 1 || ! isset($mapping['columns'], $mapping['ignored_columns']) || ! is_array($mapping['columns']) || ! is_array($mapping['ignored_columns']) || ! is_array($headers) || array_filter($headers, 'is_string') !== $headers || count(array_unique($headers)) !== count($headers)) {
            return false;
        }

        $targets = [];
        $sources = [];
        foreach ($mapping['columns'] as $source => $target) {
            if (! is_string($source) || ! in_array($source, $headers, true) || ! is_string($target) || ! $this->catalog->allows($target) || in_array($target, $targets, true)) {
                return false;
            }
            $sources[] = $source;
            $targets[] = $target;
        }

        foreach ($mapping['ignored_columns'] as $source) {
            if (! is_string($source) || ! in_array($source, $headers, true) || in_array($source, $sources, true)) {
                return false;
            }
            $sources[] = $source;
        }

        return count($sources) === count($headers) && array_diff($headers, $sources) === [];
    }

    public function hasNormalizedData(DataImport $dataImport): bool
    {
        return $dataImport->rows()->whereNotNull('normalized_data')->exists();
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array{rows: LengthAwarePaginator<int, array<string, mixed>>, counts: array{total: int, valid: int, warning: int, error: int}}
     */
    public function build(DataImport $dataImport, string $filter, int $perPage, int $page, string $path, array $query): array
    {
        $mapping = $dataImport->metadata['mapping']['columns'];
        $mappedTargets = array_values($mapping);
        $counts = ['total' => 0, 'valid' => 0, 'warning' => 0, 'error' => 0];
        $matching = 0;
        $pageRows = [];
        $first = ($page - 1) * $perPage + 1;
        $last = $page * $perPage;

        $rows = $dataImport->rows()
            ->select(['id', 'row_number', 'original_data', 'normalized_data'])
            ->lazyById(500, 'row_number');

        foreach ($rows as $row) {
            $preview = $this->previewRow($row, $mappedTargets);
            $counts['total']++;
            $counts[$preview['status']]++;

            if ($filter !== 'all' && $preview['status'] !== $filter) {
                continue;
            }

            $matching++;
            if ($matching >= $first && $matching <= $last) {
                $pageRows[] = $preview;
            }
        }

        $paginator = new LengthAwarePaginator($pageRows, $matching, $perPage, $page, [
            'path' => $path,
            'query' => $query,
        ]);

        return ['rows' => $paginator, 'counts' => $counts];
    }

    /**
     * @param  list<string>  $mappedTargets
     * @return array<string, mixed>
     */
    private function previewRow(ImportRow $row, array $mappedTargets): array
    {
        $data = $row->normalized_data ?? [];
        $validation = $this->validator->validate($data, $mappedTargets);

        return [
            'row_number' => $row->row_number,
            'status' => $validation['status'],
            'data' => $data,
            'original_data' => $row->original_data,
            'issues' => $validation['issues'],
        ];
    }
}
