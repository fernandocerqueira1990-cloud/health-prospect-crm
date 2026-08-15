<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class XlsxImportReadFilter implements IReadFilter
{
    public function __construct(private readonly int $maxRows, private readonly int $maxColumns) {}

    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
    {
        return $row <= $this->maxRows
            && Coordinate::columnIndexFromString($columnAddress) <= $this->maxColumns;
    }
}
