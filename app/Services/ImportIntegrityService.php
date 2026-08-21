<?php

namespace App\Services;

use App\Models\DataImport;
use App\Models\ImportRow;

class ImportIntegrityService
{
    public function dedupSignature(DataImport $dataImport): string
    {
        $context = hash_init('sha256', HASH_HMAC, (string) config('app.key'));
        hash_update($context, $this->encode([
            'import_id' => $dataImport->id,
            'header' => $dataImport->metadata['header'] ?? null,
            'mapping' => $dataImport->metadata['mapping']['columns'] ?? null,
        ]));

        foreach ($dataImport->rows()->select(['id', 'row_number', 'original_data', 'normalized_data', 'dedup_data'])->orderBy('id')->cursor() as $row) {
            hash_update($context, $this->encode([
                'id' => $row->id,
                'row_number' => $row->row_number,
                'original_data' => $row->original_data,
                'normalized_data' => $row->normalized_data,
                'dedup_data' => $row->dedup_data,
            ]));
        }

        return hash_final($context);
    }

    public function validDedupSignature(DataImport $dataImport): bool
    {
        $signature = $dataImport->metadata['security']['dedup_signature'] ?? null;

        return is_string($signature) && hash_equals($signature, $this->dedupSignature($dataImport));
    }

    /** @param array<string, mixed> $execution */
    public function executionSignature(DataImport $dataImport, ImportRow $row, array $execution): string
    {
        unset($execution['integrity_signature']);

        return hash_hmac('sha256', $this->encode([
            'import_id' => $dataImport->id,
            'row_id' => $row->id,
            'execution' => $execution,
        ]), (string) config('app.key'));
    }

    public function validExecutionSignature(DataImport $dataImport, ImportRow $row): bool
    {
        $execution = $row->execution_data;
        $signature = is_array($execution) ? ($execution['integrity_signature'] ?? null) : null;

        return is_array($execution) && is_string($signature)
            && hash_equals($signature, $this->executionSignature($dataImport, $row, $execution));
    }

    private function encode(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n";
    }
}
