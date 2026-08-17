<?php

namespace App\Exceptions;

use RuntimeException;

class ImportExecutionException extends RuntimeException
{
    public function __construct(public readonly string $errorCode, string $safeMessage)
    {
        parent::__construct($safeMessage);
    }
}
