<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Support\TaxIdNormalizer;

class ImportPreviewValidator
{
    public const STATUS_VALID = 'valid';

    public const STATUS_WARNING = 'warning';

    public const STATUS_ERROR = 'error';

    /** @var array<string, int> */
    private const STRING_LIMITS = [
        'company.legal_name' => 255, 'company.trade_name' => 255, 'company.tax_id' => 64,
        'company.tax_id_country' => 2, 'company.segment' => 255, 'company.category' => 255,
        'company.website' => 2048, 'company.phone' => 64, 'company.email' => 255,
        'company.street' => 255, 'company.number' => 32, 'company.complement' => 255,
        'company.district' => 255, 'company.city' => 255, 'company.state' => 100,
        'company.postal_code' => 32, 'contact.name' => 255, 'contact.job_title' => 255,
        'contact.department' => 255, 'contact.email' => 255, 'contact.phone' => 64,
        'contact.whatsapp' => 64, 'contact.linkedin_url' => 2048, 'contact.decision_role' => 32,
        'contact.influence_level' => 16, 'lead.name' => 255, 'lead.company_name' => 255,
        'lead.job_title' => 255, 'lead.email' => 255, 'lead.phone' => 64,
        'lead.whatsapp' => 64, 'lead.status' => 32, 'lead.priority' => 16,
        'lead.temperature' => 16,
    ];

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $mappedTargets
     * @return array{status: string, issues: list<array{severity: string, field: string, code: string, message: string}>}
     */
    public function validate(array $data, array $mappedTargets): array
    {
        $issues = [];
        $mapped = array_fill_keys($mappedTargets, true);

        if (! array_any(['company', 'contact', 'lead'], fn (string $group): bool => $this->groupHasData($data, $group))) {
            $issues[] = $this->issue(self::STATUS_WARNING, 'normalized_data', 'no_mapped_data', 'A linha não possui valores normalizados para revisão.');
        }

        $this->validateRequiredValues($data, $issues);

        foreach (self::STRING_LIMITS as $target => $limit) {
            if (! isset($mapped[$target]) || ! $this->hasValue($data, $target)) {
                continue;
            }

            $value = $this->value($data, $target);
            if (! is_string($value)) {
                $issues[] = $this->issue(self::STATUS_ERROR, $target, 'invalid_type', 'O valor deve ser textual.');

                continue;
            }
            if (mb_strlen($value) > $limit) {
                $issues[] = $this->issue(self::STATUS_ERROR, $target, 'value_too_long', "O valor ultrapassa o limite de {$limit} caracteres.");
            }
        }

        foreach (['company.email', 'contact.email', 'lead.email'] as $target) {
            if (isset($mapped[$target]) && $this->hasValue($data, $target) && is_string($this->value($data, $target)) && filter_var($this->value($data, $target), FILTER_VALIDATE_EMAIL) === false) {
                $issues[] = $this->issue(self::STATUS_ERROR, $target, 'invalid_email', 'O e-mail possui formato inválido.');
            }
        }

        $this->validateWebsite($data, $mapped, $issues);
        $this->validateLinkedIn($data, $mapped, $issues);
        $this->validatePhones($data, $mapped, $issues);
        $this->validateTaxId($data, $mapped, $issues);
        $this->validateEnums($data, $mapped, $issues);
        $this->validateIntegers($data, $mapped, $issues);

        $status = self::STATUS_VALID;
        foreach ($issues as $issue) {
            if ($issue['severity'] === self::STATUS_ERROR) {
                $status = self::STATUS_ERROR;
                break;
            }
            $status = self::STATUS_WARNING;
        }

        return ['status' => $status, 'issues' => $issues];
    }

    /** @param array<string, mixed> $data @param list<array{severity: string, field: string, code: string, message: string}> $issues */
    private function validateRequiredValues(array $data, array &$issues): void
    {
        if ($this->groupHasData($data, 'company') && ! $this->hasValue($data, 'company.legal_name')) {
            $issues[] = $this->issue(self::STATUS_ERROR, 'company.legal_name', 'missing_required_value', 'A razão social é obrigatória para uma empresa.');
        }
        if ($this->groupHasData($data, 'contact') && ! $this->hasValue($data, 'contact.name')) {
            $issues[] = $this->issue(self::STATUS_ERROR, 'contact.name', 'missing_required_value', 'O nome é obrigatório para um contato.');
        }
        if ($this->groupHasData($data, 'lead')) {
            $identifiers = ['lead.name', 'lead.company_name', 'lead.email', 'lead.phone', 'lead.whatsapp'];
            if (! array_any($identifiers, fn (string $target): bool => $this->hasValue($data, $target))) {
                $issues[] = $this->issue(self::STATUS_ERROR, 'lead.name', 'missing_required_value', 'O lead precisa de ao menos um dado de identificação.');
            }
        }
    }

    /** @param array<string, mixed> $data @param array<string, true> $mapped @param list<array{severity: string, field: string, code: string, message: string}> $issues */
    private function validateWebsite(array $data, array $mapped, array &$issues): void
    {
        $target = 'company.website';
        if (! isset($mapped[$target]) || ! $this->hasValue($data, $target) || ! is_string($this->value($data, $target))) {
            return;
        }
        $value = $this->value($data, $target);
        $parts = parse_url($value);
        if (filter_var($value, FILTER_VALIDATE_URL) === false || ! is_array($parts) || ! isset($parts['scheme'], $parts['host']) || ! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            $issues[] = $this->issue(self::STATUS_ERROR, $target, 'invalid_url', 'O site deve ser uma URL HTTP ou HTTPS válida.');
        }
    }

    /** @param array<string, mixed> $data @param array<string, true> $mapped @param list<array{severity: string, field: string, code: string, message: string}> $issues */
    private function validateLinkedIn(array $data, array $mapped, array &$issues): void
    {
        $target = 'contact.linkedin_url';
        if (! isset($mapped[$target]) || ! $this->hasValue($data, $target) || ! is_string($this->value($data, $target))) {
            return;
        }
        $value = $this->value($data, $target);
        $host = parse_url($value, PHP_URL_HOST);
        $scheme = parse_url($value, PHP_URL_SCHEME);
        if (filter_var($value, FILTER_VALIDATE_URL) === false || ! is_string($host) || ! is_string($scheme) || ! in_array(strtolower($scheme), ['http', 'https'], true) || preg_match('/(^|\.)linkedin\.com$/i', $host) !== 1) {
            $issues[] = $this->issue(self::STATUS_ERROR, $target, 'invalid_url', 'Informe uma URL HTTP ou HTTPS válida do LinkedIn.');
        }
    }

    /** @param array<string, mixed> $data @param array<string, true> $mapped @param list<array{severity: string, field: string, code: string, message: string}> $issues */
    private function validatePhones(array $data, array $mapped, array &$issues): void
    {
        foreach (['company.phone', 'contact.phone', 'contact.whatsapp', 'lead.phone', 'lead.whatsapp'] as $target) {
            if (! isset($mapped[$target]) || ! $this->hasValue($data, $target)) {
                continue;
            }
            $value = $this->value($data, $target);
            if (! is_string($value) || preg_match('/^\+?\d{6,20}$/', $value) !== 1) {
                $issues[] = $this->issue(self::STATUS_WARNING, $target, 'invalid_phone', 'O telefone parece incompleto ou ambíguo e merece revisão.');
            }
        }
    }

    /** @param array<string, mixed> $data @param array<string, true> $mapped @param list<array{severity: string, field: string, code: string, message: string}> $issues */
    private function validateTaxId(array $data, array $mapped, array &$issues): void
    {
        if (isset($mapped['company.tax_id_country']) && $this->hasValue($data, 'company.tax_id_country')) {
            $country = $this->value($data, 'company.tax_id_country');
            if (! is_string($country) || preg_match('/^[A-Z]{2}$/', $country) !== 1) {
                $issues[] = $this->issue(self::STATUS_ERROR, 'company.tax_id_country', 'invalid_country', 'O país da identificação fiscal deve usar duas letras maiúsculas.');
            }
            if (! $this->hasValue($data, 'company.tax_id')) {
                $issues[] = $this->issue(self::STATUS_ERROR, 'company.tax_id', 'missing_required_value', 'A identificação fiscal é obrigatória quando o país foi informado.');
            }
        }
        if (! isset($mapped['company.tax_id']) || ! $this->hasValue($data, 'company.tax_id')) {
            return;
        }
        if (! $this->hasValue($data, 'company.tax_id_country')) {
            $issues[] = $this->issue(self::STATUS_WARNING, 'company.tax_id_country', 'tax_country_missing', 'Informe o país para validar corretamente a identificação fiscal.');

            return;
        }
        $country = $this->value($data, 'company.tax_id_country');
        $taxId = $this->value($data, 'company.tax_id');
        if ($country === 'BR' && (! is_string($taxId) || ! TaxIdNormalizer::isValidCnpj($taxId))) {
            $issues[] = $this->issue(self::STATUS_ERROR, 'company.tax_id', 'invalid_tax_id', 'O CNPJ informado é inválido.');
        } elseif (is_string($country) && $country !== 'BR' && (! is_string($taxId) || preg_match('/^[\pL\pN][\pL\pN.\-\/ ]*$/u', $taxId) !== 1)) {
            $issues[] = $this->issue(self::STATUS_ERROR, 'company.tax_id', 'invalid_tax_id', 'A identificação fiscal possui caracteres inválidos.');
        }
    }

    /** @param array<string, mixed> $data @param array<string, true> $mapped @param list<array{severity: string, field: string, code: string, message: string}> $issues */
    private function validateEnums(array $data, array $mapped, array &$issues): void
    {
        $enums = [
            'company.priority' => Company::PRIORITIES,
            'contact.decision_role' => Contact::DECISION_ROLES,
            'contact.influence_level' => Contact::INFLUENCE_LEVELS,
            'lead.status' => Lead::STATUSES,
            'lead.priority' => Lead::PRIORITIES,
            'lead.temperature' => Lead::TEMPERATURES,
        ];
        foreach ($enums as $target => $allowed) {
            if (isset($mapped[$target]) && $this->hasValue($data, $target) && ! in_array($this->value($data, $target), $allowed, true)) {
                $issues[] = $this->issue(self::STATUS_ERROR, $target, 'invalid_enum', 'O valor não pertence às opções permitidas.');
            }
        }
    }

    /** @param array<string, mixed> $data @param array<string, true> $mapped @param list<array{severity: string, field: string, code: string, message: string}> $issues */
    private function validateIntegers(array $data, array $mapped, array &$issues): void
    {
        foreach (['company.employee_count_estimate' => 4294967295, 'lead.score' => 100] as $target => $maximum) {
            if (! isset($mapped[$target]) || ! $this->hasValue($data, $target)) {
                continue;
            }
            $value = $this->value($data, $target);
            if (! is_int($value) || $value < 0 || $value > $maximum) {
                $issues[] = $this->issue(self::STATUS_ERROR, $target, 'invalid_integer', "O valor deve ser um inteiro entre 0 e {$maximum}.");
            }
        }
    }

    /** @param array<string, mixed> $data */
    private function groupHasData(array $data, string $group): bool
    {
        return isset($data[$group]) && is_array($data[$group]) && $data[$group] !== [];
    }

    /** @param array<string, mixed> $data */
    private function hasValue(array $data, string $target): bool
    {
        $value = $this->value($data, $target);

        return $value !== null && $value !== '';
    }

    /** @param array<string, mixed> $data */
    private function value(array $data, string $target): mixed
    {
        [$group, $field] = explode('.', $target, 2);

        return isset($data[$group]) && is_array($data[$group]) ? ($data[$group][$field] ?? null) : null;
    }

    /** @return array{severity: string, field: string, code: string, message: string} */
    private function issue(string $severity, string $field, string $code, string $message): array
    {
        return compact('severity', 'field', 'code', 'message');
    }
}
