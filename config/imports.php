<?php

return [
    'disk' => env('IMPORTS_DISK', 'imports'),
    'max_upload_kb' => (int) env('IMPORTS_MAX_UPLOAD_KB', 10240),
    'batch_size' => (int) env('IMPORTS_BATCH_SIZE', 500),
    'xlsx_max_rows' => (int) env('IMPORTS_XLSX_MAX_ROWS', 100000),
    'xlsx_max_columns' => (int) env('IMPORTS_XLSX_MAX_COLUMNS', 250),
    'xlsx_max_archive_entries' => (int) env('IMPORTS_XLSX_MAX_ARCHIVE_ENTRIES', 10000),
    'xlsx_max_uncompressed_bytes' => (int) env('IMPORTS_XLSX_MAX_UNCOMPRESSED_BYTES', 67108864),
    'xlsx_max_compression_ratio' => (int) env('IMPORTS_XLSX_MAX_COMPRESSION_RATIO', 100),
    'requires_local_disk' => true,
];
