<?php

namespace App\Http\Requests\Contacts\Concerns;

use App\Support\EmailNormalizer;
use App\Support\PhoneNormalizer;

trait NormalizesContactInput
{
    protected function normalizeContactInput(): void
    {
        $normalized = [];
        foreach (['name', 'job_title', 'department', 'email', 'phone', 'whatsapp', 'linkedin_url', 'decision_role', 'influence_level', 'notes'] as $field) {
            if (! $this->has($field)) {
                continue;
            }
            $value = trim((string) $this->input($field));
            $normalized[$field] = $value === '' ? null : $value;
        }
        if (isset($normalized['email'])) {
            $normalized['email'] = EmailNormalizer::normalize($normalized['email']);
        }
        foreach (['phone', 'whatsapp'] as $field) {
            if (isset($normalized[$field])) {
                $normalized[$field] = PhoneNormalizer::normalize($normalized[$field]);
            }
        }
        $normalized['is_primary'] = $this->boolean('is_primary');
        $normalized['active'] = $this->has('active') ? $this->boolean('active') : true;
        $this->merge($normalized);
    }
}
