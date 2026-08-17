<?php

namespace App\Exceptions;

use RuntimeException;

class XlsxImportException extends RuntimeException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct('XLSX import failed: '.$reason);
    }
}
