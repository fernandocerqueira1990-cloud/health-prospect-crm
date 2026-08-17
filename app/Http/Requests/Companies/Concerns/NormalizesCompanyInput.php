<?php

namespace App\Http\Requests\Companies\Concerns;

use App\Support\EmailNormalizer;
use App\Support\TaxIdNormalizer;
use App\Support\WebsiteNormalizer;

trait NormalizesCompanyInput
{
    protected function normalizeCompanyInput(): void
    {
        $nullableStrings = [
            'trade_name', 'tax_id', 'tax_id_country', 'segment', 'category', 'website', 'phone', 'email',
            'street', 'number', 'complement', 'district', 'city', 'state', 'postal_code',
            'priority', 'notes',
        ];
        $normalized = [];

        if ($this->has('legal_name')) {
            $normalized['legal_name'] = trim((string) $this->input('legal_name'));
        }

        foreach ($nullableStrings as $field) {
            if ($this->has($field)) {
                $value = trim((string) $this->input($field));
                $normalized[$field] = $value === '' ? null : $value;
            }
        }

        if (array_key_exists('tax_id_country', $normalized) && $normalized['tax_id_country'] !== null) {
            $normalized['tax_id_country'] = strtoupper($normalized['tax_id_country']);
        }

        if (array_key_exists('tax_id', $normalized)) {
            $country = $normalized['tax_id_country'] ?? $this->input('tax_id_country');
            $normalized['tax_id'] = TaxIdNormalizer::normalize($normalized['tax_id'], is_string($country) ? $country : null);

            if ($normalized['tax_id'] === null) {
                $normalized['tax_id_country'] = null;
            }
        }

        if (array_key_exists('email', $normalized) && $normalized['email'] !== null) {
            $normalized['email'] = EmailNormalizer::normalize($normalized['email']);
        }

        if (array_key_exists('website', $normalized)) {
            $normalized['website'] = WebsiteNormalizer::normalize($normalized['website']);
        }

        if (array_key_exists('phone', $normalized) && $normalized['phone'] !== null) {
            $normalized['phone'] = preg_replace('/\s+/', ' ', $normalized['phone']);
        }

        if (array_key_exists('state', $normalized) && preg_match('/^[a-z]{2}$/i', (string) $normalized['state']) === 1) {
            $normalized['state'] = strtoupper((string) $normalized['state']);
        }

        $this->merge($normalized);
    }
}
