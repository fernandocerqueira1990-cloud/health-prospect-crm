<?php

namespace App\Services;

use App\Models\ImportRow;

class ImportDedupKeyRegistry
{
    /** @var array<string, array<string, array{row_id: int, row_number: int}>> */
    private array $keys = ['company' => [], 'contact' => [], 'lead' => []];

    /** @return array{row_id: int, row_number: int}|null */
    public function priorOrRemember(string $entity, string $key, ImportRow $row): ?array
    {
        if (isset($this->keys[$entity][$key])) {
            return $this->keys[$entity][$key];
        }

        $this->keys[$entity][$key] = ['row_id' => $row->id, 'row_number' => $row->row_number];

        return null;
    }
}
