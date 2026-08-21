<?php

namespace App\Services;

class ImportCellSanitizer
{
    public function asData(string $value): string
    {
        // Neutralize spreadsheet formula injection without interpreting or executing it.
        return preg_match('/^[\x00-\x20]*[=+\-@]/u', $value) === 1 ? "'".$value : $value;
    }
}
