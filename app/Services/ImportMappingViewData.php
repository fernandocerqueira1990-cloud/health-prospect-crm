<?php

namespace App\Services;

use App\Models\DataImport;

class ImportMappingViewData
{
    /** @param list<string> $headers @return array<string, list<string>> */
    public function samples(DataImport $dataImport, array $headers): array
    {
        $samples = array_fill_keys($headers, []);
        $rows = $dataImport->rows()->orderBy('id')->limit(50)->get(['original_data']);

        foreach ($rows as $row) {
            foreach ($headers as $header) {
                $value = $row->original_data[$header] ?? null;
                if ($value === null || $value === '' || ! is_scalar($value)) {
                    continue;
                }
                $display = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
                if (! in_array($display, $samples[$header], true)) {
                    $samples[$header][] = $display;
                    $samples[$header] = array_slice($samples[$header], 0, 3);
                }
            }
        }

        return $samples;
    }
}
