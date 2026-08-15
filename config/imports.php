<?php

return [
    'disk' => env('IMPORTS_DISK', 'imports'),
    'max_upload_kb' => (int) env('IMPORTS_MAX_UPLOAD_KB', 10240),
    'batch_size' => (int) env('IMPORTS_BATCH_SIZE', 500),
    'requires_local_disk' => true,
];
