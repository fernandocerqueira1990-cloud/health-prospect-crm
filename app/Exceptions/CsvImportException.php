<?php

namespace App\Exceptions;

use RuntimeException;

class CsvImportException extends RuntimeException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct('CSV import failed: '.$reason);
    }
}
