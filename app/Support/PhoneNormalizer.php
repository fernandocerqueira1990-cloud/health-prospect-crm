<?php

namespace App\Support;

class PhoneNormalizer
{
    public static function normalize(string $phone): string
    {
        $phone = trim($phone);
        $hasInternationalPrefix = str_starts_with($phone, '+');
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        return ($hasInternationalPrefix ? '+' : '').$digits;
    }

    public static function searchCandidate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '' || preg_match('/^[\d\s+().\-\/]+$/', $value) !== 1) {
            return null;
        }

        $normalized = self::normalize($value);
        $digits = ltrim($normalized, '+');

        return strlen($digits) >= 6 ? $normalized : null;
    }
}
