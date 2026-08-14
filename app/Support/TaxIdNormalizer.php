<?php

namespace App\Support;

class TaxIdNormalizer
{
    public static function normalize(?string $taxId, ?string $country): ?string
    {
        $taxId = trim((string) $taxId);

        if ($taxId === '') {
            return null;
        }

        if (strtoupper(trim((string) $country)) === 'BR') {
            return preg_replace('/\D+/', '', $taxId);
        }

        return $taxId;
    }

    public static function isValidCnpj(string $cnpj): bool
    {
        if (preg_match('/^\d{14}$/', $cnpj) !== 1 || preg_match('/^(\d)\1{13}$/', $cnpj) === 1) {
            return false;
        }

        foreach ([12, 13] as $length) {
            $sum = 0;
            $weight = $length - 7;

            for ($index = 0; $index < $length; $index++) {
                $sum += (int) $cnpj[$index] * $weight;
                $weight = $weight === 2 ? 9 : $weight - 1;
            }

            $digit = $sum % 11 < 2 ? 0 : 11 - ($sum % 11);

            if ((int) $cnpj[$length] !== $digit) {
                return false;
            }
        }

        return true;
    }

    public static function format(?string $taxId, ?string $country): ?string
    {
        if ($country !== 'BR' || $taxId === null || preg_match('/^\d{14}$/', $taxId) !== 1) {
            return $taxId;
        }

        return substr($taxId, 0, 2).'.'.substr($taxId, 2, 3).'.'.substr($taxId, 5, 3).'/'.substr($taxId, 8, 4).'-'.substr($taxId, 12, 2);
    }

    public static function brazilianSearchCandidate(string $taxId): ?string
    {
        if (preg_match('/^[\d.\/\-\s]+$/', $taxId) !== 1) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $taxId);

        return $digits === '' ? null : $digits;
    }
}
