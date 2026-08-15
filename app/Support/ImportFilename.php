<?php

namespace App\Support;

class ImportFilename
{
    private const MAX_LENGTH = 255;

    public static function sanitize(string $filename): string
    {
        $filename = str_replace('\\', '/', mb_scrub($filename, 'UTF-8'));
        $filename = basename($filename);
        $filename = preg_replace('/\p{Cc}+/u', '', $filename) ?? '';
        $filename = trim($filename);

        if ($filename === '' || $filename === '.' || $filename === '..') {
            return 'arquivo.csv';
        }

        if (mb_strlen($filename) <= self::MAX_LENGTH) {
            return $filename;
        }

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION)) === 'csv'
            ? '.csv'
            : '';

        return mb_substr($filename, 0, self::MAX_LENGTH - mb_strlen($extension)).$extension;
    }
}
