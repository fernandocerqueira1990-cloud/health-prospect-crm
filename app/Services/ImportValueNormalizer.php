<?php

namespace App\Services;

use App\Support\EmailNormalizer;
use App\Support\PhoneNormalizer;
use App\Support\TaxIdNormalizer;
use App\Support\WebsiteNormalizer;
use Illuminate\Support\Str;

class ImportValueNormalizer
{
    /** @param array<string, mixed> $original @param array<string, string> $mapping @return array<string, array<string, mixed>> */
    public function normalize(array $original, array $mapping): array
    {
        $normalized = [];
        $country = $this->mappedValue('company.tax_id_country', $original, $mapping);
        $normalizedCountry = is_scalar($country) ? strtoupper($this->cleanString((string) $country)) : null;

        foreach ($mapping as $source => $target) {
            $value = $this->normalizeValue($target, $original[$source] ?? null, $normalizedCountry);
            if ($value === null || $value === '') {
                continue;
            }

            [$group, $field] = explode('.', $target, 2);
            $normalized[$group][$field] = $value;
        }

        return $normalized;
    }

    private function normalizeValue(string $target, mixed $value, ?string $country): mixed
    {
        if ($value === null || (! is_scalar($value))) {
            return null;
        }

        if (is_string($value)) {
            $value = $this->cleanString($value);
        }
        if ($value === '') {
            return null;
        }

        if (in_array($target, ['company.email', 'contact.email', 'lead.email'], true)) {
            return EmailNormalizer::normalize((string) $value);
        }
        if (in_array($target, ['company.phone', 'contact.phone', 'contact.whatsapp', 'lead.phone', 'lead.whatsapp'], true)) {
            return PhoneNormalizer::normalize((string) $value);
        }
        if ($target === 'company.website') {
            return WebsiteNormalizer::normalize((string) $value);
        }
        if ($target === 'company.tax_id') {
            return TaxIdNormalizer::normalize((string) $value, $country);
        }
        if ($target === 'company.tax_id_country') {
            return strtoupper((string) $value);
        }
        if (in_array($target, ['company.priority', 'lead.priority'], true)) {
            return $this->alias((string) $value, ['baixa' => 'low', 'baixo' => 'low', 'media' => 'medium', 'medio' => 'medium', 'alta' => 'high', 'alto' => 'high', 'critica' => 'critical', 'critico' => 'critical']);
        }
        if ($target === 'lead.temperature') {
            return $this->alias((string) $value, ['frio' => 'cold', 'morno' => 'warm', 'quente' => 'hot']);
        }
        if (in_array($target, ['company.employee_count_estimate', 'lead.score'], true)) {
            return $this->integerWhenUnambiguous($value);
        }

        return $value;
    }

    private function cleanString(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }

    /** @param array<string, string> $aliases */
    private function alias(string $value, array $aliases): string
    {
        $key = Str::lower(Str::ascii($this->cleanString($value)));

        return $aliases[$key] ?? $value;
    }

    private function integerWhenUnambiguous(mixed $value): mixed
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_float($value) && is_finite($value) && floor($value) === $value && $value <= PHP_INT_MAX && $value >= PHP_INT_MIN) {
            return (int) $value;
        }
        if (is_string($value) && preg_match('/^[+-]?(0|[1-9]\d*)$/', $value) === 1) {
            $integer = filter_var($value, FILTER_VALIDATE_INT);

            return $integer === false ? $value : $integer;
        }

        return $value;
    }

    /** @param array<string, mixed> $original @param array<string, string> $mapping */
    private function mappedValue(string $target, array $original, array $mapping): mixed
    {
        $source = array_search($target, $mapping, true);

        return $source === false ? null : ($original[$source] ?? null);
    }
}
