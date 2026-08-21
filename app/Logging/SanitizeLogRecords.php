<?php

namespace App\Logging;

use App\Support\LogSanitizer;
use Illuminate\Log\Logger;
use Monolog\LogRecord;

class SanitizeLogRecords
{
    public function __construct(private readonly LogSanitizer $sanitizer) {}

    public function __invoke(Logger $logger): void
    {
        $logger->pushProcessor(fn (LogRecord $record): LogRecord => $record->with(
            message: $this->sanitizer->sanitizeMessage($record->message),
            context: $this->sanitizer->sanitize($record->context),
            extra: $this->sanitizer->sanitize($record->extra),
        ));
    }
}
