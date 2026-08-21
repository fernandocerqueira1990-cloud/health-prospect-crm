<?php

namespace App\Support;

use App\Models\DataImport;
use RuntimeException;

class ImportStoragePath
{
    public static function assertSafe(DataImport $dataImport): string
    {
        $extension = preg_quote($dataImport->type, '/');

        if (! in_array($dataImport->type, [DataImport::TYPE_CSV, DataImport::TYPE_XLSX], true)
            || preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\.'.$extension.'$/i', $dataImport->filename) !== 1) {
            throw new RuntimeException('O caminho interno da importação é inválido.');
        }

        return $dataImport->filename;
    }
}
