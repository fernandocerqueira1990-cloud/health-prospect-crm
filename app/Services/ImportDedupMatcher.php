<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Contact;
use App\Models\ImportRow;
use App\Models\Lead;
use App\Support\EmailNormalizer;
use App\Support\PhoneNormalizer;
use App\Support\TaxIdNormalizer;
use App\Support\WebsiteNormalizer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportDedupMatcher
{
    private const CANDIDATE_LIMIT = 5;

    /**
     * @param  Collection<int, ImportRow>  $rows
     * @param  list<string>  $mappedTargets
     * @return array<int, array<string, mixed>>
     */
    public function match(Collection $rows, ImportDedupKeyRegistry $registry, array $mappedTargets, ImportPreviewValidator $validator): array
    {
        $companies = $this->companyCandidates($rows);
        $contacts = $this->contactCandidates($rows);
        $leads = $this->leadCandidates($rows);
        $results = [];

        foreach ($rows as $row) {
            $data = is_array($row->normalized_data) ? $row->normalized_data : [];
            $preview = $validator->validate($data, $mappedTargets);
            $groups = [];

            foreach (['company', 'contact', 'lead'] as $group) {
                $values = $data[$group] ?? null;
                if (! is_array($values) || $values === []) {
                    continue;
                }

                if ($group === 'company') {
                    $candidates = $this->matchCompany($values, $companies, $registry, $row);
                } elseif ($group === 'contact') {
                    $candidates = $this->matchContact($values, $data['company'] ?? [], $contacts, $registry, $row);
                } else {
                    $candidates = $this->matchLead($values, $leads, $registry, $row);
                }
                $groups[$group] = [
                    'match' => $this->matchStrength($candidates),
                    'candidates' => array_slice($candidates, 0, self::CANDIDATE_LIMIT),
                    'decision' => ['action' => $candidates === [] ? 'create_new' : 'pending', 'candidate_source' => null, 'candidate_id' => null],
                ];
            }

            $status = $preview['status'] === ImportPreviewValidator::STATUS_ERROR
                ? 'blocked'
                : (array_any($groups, fn (array $group): bool => $group['candidates'] !== []) ? 'review' : 'clear');

            $results[$row->id] = [
                'version' => 1,
                'analyzed_at' => now()->toIso8601String(),
                'status' => $status,
                'groups' => $groups,
            ];
        }

        return $results;
    }

    /** @param Collection<int, ImportRow> $rows @return Collection<int, Company> */
    private function companyCandidates(Collection $rows): Collection
    {
        $values = $this->groupValues($rows, 'company');
        $taxIds = $this->values($values, 'tax_id');
        $emails = $this->values($values, 'email', fn (string $value): string => EmailNormalizer::normalize($value));
        $phones = $this->values($values, 'phone', fn (string $value): string => PhoneNormalizer::normalize($value));
        $websites = $this->values($values, 'website', fn (string $value): string => (string) WebsiteNormalizer::normalize($value));
        $names = array_values(array_unique(array_merge($this->values($values, 'legal_name'), $this->values($values, 'trade_name'))));

        if ($taxIds === [] && $emails === [] && $phones === [] && $websites === [] && $names === []) {
            return collect();
        }

        return Company::withTrashed()->select(['id', 'legal_name', 'trade_name', 'tax_id', 'tax_id_country', 'email', 'phone', 'website', 'city', 'state', 'deleted_at'])
            ->where(function ($query) use ($taxIds, $emails, $phones, $websites, $names): void {
                if ($taxIds !== []) {
                    $query->orWhereIn('tax_id', $taxIds);
                }
                if ($emails !== []) {
                    $query->orWhereIn('email', $emails);
                }
                if ($phones !== []) {
                    $query->orWhereIn(DB::raw("regexp_replace(phone, '[^0-9]', '', 'g')"), $this->phoneDigits($phones));
                }
                if ($websites !== []) {
                    $query->orWhereIn('website', $websites);
                }
                if ($names !== []) {
                    $lower = array_map(fn (string $name): string => $this->text($name), $names);
                    $query->orWhereIn(DB::raw("LOWER(TRIM(regexp_replace(legal_name, '[[:space:]]+', ' ', 'g')))"), $lower)->orWhereIn(DB::raw("LOWER(TRIM(regexp_replace(trade_name, '[[:space:]]+', ' ', 'g')))"), $lower);
                }
            })->get();
    }

    /** @param Collection<int, ImportRow> $rows @return Collection<int, Contact> */
    private function contactCandidates(Collection $rows): Collection
    {
        $values = $this->groupValues($rows, 'contact');
        $linkedIn = $this->values($values, 'linkedin_url');
        $emails = $this->values($values, 'email', fn (string $value): string => EmailNormalizer::normalize($value));
        $phones = array_values(array_unique(array_merge($this->values($values, 'phone', fn (string $value): string => PhoneNormalizer::normalize($value)), $this->values($values, 'whatsapp', fn (string $value): string => PhoneNormalizer::normalize($value)))));
        $names = $this->values($values, 'name');
        if ($linkedIn === [] && $emails === [] && $phones === [] && $names === []) {
            return collect();
        }

        return Contact::withTrashed()->with('company:id,legal_name,trade_name,tax_id,tax_id_country,deleted_at')
            ->select(['id', 'company_id', 'name', 'job_title', 'email', 'phone', 'whatsapp', 'linkedin_url', 'active', 'deleted_at'])
            ->where(function ($query) use ($linkedIn, $emails, $phones, $names): void {
                if ($linkedIn !== []) {
                    $query->orWhereIn('linkedin_url', $linkedIn);
                }
                if ($emails !== []) {
                    $query->orWhereIn('email', $emails);
                }
                if ($phones !== []) {
                    $digits = $this->phoneDigits($phones);
                    $query->orWhereIn(DB::raw("regexp_replace(phone, '[^0-9]', '', 'g')"), $digits)->orWhereIn(DB::raw("regexp_replace(whatsapp, '[^0-9]', '', 'g')"), $digits);
                }
                if ($names !== []) {
                    $query->orWhereIn(DB::raw("LOWER(TRIM(regexp_replace(name, '[[:space:]]+', ' ', 'g')))"), array_map(fn (string $name): string => $this->text($name), $names));
                }
            })->get();
    }

    /** @param Collection<int, ImportRow> $rows @return Collection<int, Lead> */
    private function leadCandidates(Collection $rows): Collection
    {
        $values = $this->groupValues($rows, 'lead');
        $emails = $this->values($values, 'email', fn (string $value): string => EmailNormalizer::normalize($value));
        $phones = array_values(array_unique(array_merge($this->values($values, 'phone', fn (string $value): string => PhoneNormalizer::normalize($value)), $this->values($values, 'whatsapp', fn (string $value): string => PhoneNormalizer::normalize($value)))));
        $names = $this->values($values, 'name');
        if ($emails === [] && $phones === [] && $names === []) {
            return collect();
        }

        return Lead::withTrashed()->select(['id', 'name', 'company_name', 'email', 'phone', 'whatsapp', 'status', 'deleted_at'])
            ->where(function ($query) use ($emails, $phones, $names): void {
                if ($emails !== []) {
                    $query->orWhereIn('email', $emails);
                }
                if ($phones !== []) {
                    $digits = $this->phoneDigits($phones);
                    $query->orWhereIn(DB::raw("regexp_replace(phone, '[^0-9]', '', 'g')"), $digits)->orWhereIn(DB::raw("regexp_replace(whatsapp, '[^0-9]', '', 'g')"), $digits);
                }
                if ($names !== []) {
                    $query->orWhereIn(DB::raw("LOWER(TRIM(regexp_replace(name, '[[:space:]]+', ' ', 'g')))"), array_map(fn (string $name): string => $this->text($name), $names));
                }
            })->get();
    }

    /** @param array<string, mixed> $data @param Collection<int, Company> $crm @return list<array<string, mixed>> */
    private function matchCompany(array $data, Collection $crm, ImportDedupKeyRegistry $registry, ImportRow $row): array
    {
        $matches = [];
        $country = $this->string($data['tax_id_country'] ?? null);
        $taxId = TaxIdNormalizer::normalize($this->string($data['tax_id'] ?? null), $country);
        foreach ($crm as $company) {
            $reasons = [];
            $strength = 'possible';
            if ($country !== null && $taxId !== null && $company->tax_id_country === strtoupper($country) && $company->tax_id === $taxId) {
                $reasons[] = 'tax_id_country_tax_id';
                $strength = 'exact';
            }
            foreach (['email', 'phone', 'website', 'legal_name', 'trade_name'] as $field) {
                if ($this->sameField($data, $field, $company->{$field}, $field)) {
                    $reasons[] = $field;
                }
            }
            if ($reasons !== []) {
                $matches[] = $this->crmCandidate('company', $company->id, $strength, $reasons, $company->trashed());
            }
        }

        $keys = [];
        if ($country !== null && $taxId !== null) {
            $keys['tax:'.strtoupper($country).':'.$taxId] = ['exact', 'tax_id_country_tax_id'];
        }
        foreach (['email', 'phone', 'website', 'legal_name', 'trade_name'] as $field) {
            $key = $this->fieldKey($field, $data[$field] ?? null);
            if ($key !== null) {
                $keys[$field.':'.$key] = ['possible', $field];
            }
        }
        $matches = array_merge($matches, $this->internalCandidates('company', $keys, $registry, $row));

        return $this->sortCandidates($matches);
    }

    /** @param array<string, mixed> $data @param array<string, mixed> $companyData @param Collection<int, Contact> $crm @return list<array<string, mixed>> */
    private function matchContact(array $data, array $companyData, Collection $crm, ImportDedupKeyRegistry $registry, ImportRow $row): array
    {
        $matches = [];
        $companyKey = $this->companyContext($companyData);
        foreach ($crm as $contact) {
            $sameCompany = $companyKey !== null && $companyKey === $this->companyContext($contact->company?->toArray() ?? []);
            $reasons = [];
            $strength = 'possible';
            if ($this->sameField($data, 'linkedin_url', $contact->linkedin_url, 'linkedin_url')) {
                $reasons[] = 'linkedin_url';
                $strength = 'exact';
            }
            foreach (['email', 'phone', 'whatsapp'] as $field) {
                if ($this->sameField($data, $field, $contact->{$field}, $field)) {
                    $reasons[] = $sameCompany ? $field.'_same_company' : $field;
                    if ($sameCompany) {
                        $strength = 'exact';
                    }
                }
            }
            if ($sameCompany && $this->sameField($data, 'name', $contact->name, 'name')) {
                $reasons[] = 'name_same_company';
            }
            if ($reasons !== []) {
                $matches[] = $this->crmCandidate('contact', $contact->id, $strength, $reasons, $contact->trashed());
            }
        }

        $keys = [];
        if (($linkedin = $this->fieldKey('linkedin_url', $data['linkedin_url'] ?? null)) !== null) {
            $keys['linkedin:'.$linkedin] = ['exact', 'linkedin_url'];
        }
        foreach (['email', 'phone', 'whatsapp'] as $field) {
            $key = $this->fieldKey($field, $data[$field] ?? null);
            if ($key !== null) {
                $keys[$field.':'.($companyKey ?? 'no-company').':'.$key] = [$companyKey === null ? 'possible' : 'exact', $companyKey === null ? $field : $field.'_same_company'];
            }
        }
        if ($companyKey !== null && ($name = $this->fieldKey('name', $data['name'] ?? null)) !== null) {
            $keys['name:'.$companyKey.':'.$name] = ['possible', 'name_same_company'];
        }
        $matches = array_merge($matches, $this->internalCandidates('contact', $keys, $registry, $row));

        return $this->sortCandidates($matches);
    }

    /** @param array<string, mixed> $data @param Collection<int, Lead> $crm @return list<array<string, mixed>> */
    private function matchLead(array $data, Collection $crm, ImportDedupKeyRegistry $registry, ImportRow $row): array
    {
        $matches = [];
        foreach ($crm as $lead) {
            $reasons = [];
            $strength = 'possible';
            foreach (['email', 'phone', 'whatsapp'] as $field) {
                if ($this->sameField($data, $field, $lead->{$field}, $field)) {
                    $reasons[] = $field;
                    $strength = 'exact';
                }
            }
            if ($this->sameField($data, 'name', $lead->name, 'name') && $this->sameField($data, 'company_name', $lead->company_name, 'company_name')) {
                $reasons[] = 'name_company_name';
            }
            if ($reasons !== []) {
                $matches[] = $this->crmCandidate('lead', $lead->id, $strength, $reasons, $lead->trashed());
            }
        }
        $keys = [];
        foreach (['email', 'phone', 'whatsapp'] as $field) {
            $key = $this->fieldKey($field, $data[$field] ?? null);
            if ($key !== null) {
                $keys[$field.':'.$key] = ['exact', $field];
            }
        }
        $name = $this->fieldKey('name', $data['name'] ?? null);
        $company = $this->fieldKey('company_name', $data['company_name'] ?? null);
        if ($name !== null && $company !== null) {
            $keys['name-company:'.$name.':'.$company] = ['possible', 'name_company_name'];
        }
        $matches = array_merge($matches, $this->internalCandidates('lead', $keys, $registry, $row));

        return $this->sortCandidates($matches);
    }

    /** @param array<string, array{string, string}> $keys @return list<array<string, mixed>> */
    private function internalCandidates(string $entity, array $keys, ImportDedupKeyRegistry $registry, ImportRow $row): array
    {
        $candidates = [];
        foreach ($keys as $key => [$strength, $reason]) {
            $prior = $registry->priorOrRemember($entity, $key, $row);
            if ($prior !== null) {
                $index = (string) $prior['row_id'];
                if (! isset($candidates[$index])) {
                    $candidates[$index] = ['source' => 'import', 'entity' => $entity, 'import_row_id' => $prior['row_id'], 'row_number' => $prior['row_number'], 'strength' => $strength, 'reasons' => []];
                }
                if ($strength === 'exact') {
                    $candidates[$index]['strength'] = 'exact';
                }
                $candidates[$index]['reasons'][] = $reason;
            }
        }

        return array_values($candidates);
    }

    /** @param list<array<string, mixed>> $candidates @return list<array<string, mixed>> */
    private function sortCandidates(array $candidates): array
    {
        usort($candidates, fn (array $a, array $b): int => [$a['strength'] === 'exact' ? 0 : 1, -count($a['reasons']), $a['source'], $a['id'] ?? $a['import_row_id']] <=> [$b['strength'] === 'exact' ? 0 : 1, -count($b['reasons']), $b['source'], $b['id'] ?? $b['import_row_id']]);

        return $candidates;
    }

    /** @param list<array<string, mixed>> $candidates */
    private function matchStrength(array $candidates): string
    {
        return array_any($candidates, fn (array $candidate): bool => $candidate['strength'] === 'exact') ? 'exact' : ($candidates === [] ? 'none' : 'possible');
    }

    /** @return array<string, mixed> */
    private function crmCandidate(string $entity, int $id, string $strength, array $reasons, bool $archived): array
    {
        return compact('entity', 'id', 'strength', 'reasons', 'archived') + ['source' => 'crm'];
    }

    /** @param Collection<int, ImportRow> $rows @return list<array<string, mixed>> */
    private function groupValues(Collection $rows, string $group): array
    {
        return $rows->map(fn (ImportRow $row): mixed => $row->normalized_data[$group] ?? null)->filter(fn (mixed $value): bool => is_array($value))->values()->all();
    }

    /** @param list<array<string, mixed>> $groups @return list<string> */
    private function values(array $groups, string $field, ?callable $normalize = null): array
    {
        $values = [];
        foreach ($groups as $group) {
            $value = $this->string($group[$field] ?? null);
            if ($value !== null) {
                $values[] = $normalize ? $normalize($value) : $value;
            }
        }

        return array_values(array_unique($values));
    }

    private function sameField(array $data, string $field, mixed $candidate, string $kind): bool
    {
        $left = $this->fieldKey($kind, $data[$field] ?? null);
        $right = $this->fieldKey($kind, $candidate);

        return $left !== null && $left === $right;
    }

    private function fieldKey(string $kind, mixed $value): ?string
    {
        $value = $this->string($value);
        if ($value === null) {
            return null;
        }

        return match ($kind) {
            'email' => EmailNormalizer::normalize($value),
            'phone', 'whatsapp' => PhoneNormalizer::normalize($value),
            'website' => (string) WebsiteNormalizer::normalize($value),
            default => $this->text($value),
        };
    }

    /** @param array<string, mixed> $company */
    private function companyContext(array $company): ?string
    {
        $country = $this->string($company['tax_id_country'] ?? null);
        $taxId = TaxIdNormalizer::normalize($this->string($company['tax_id'] ?? null), $country);
        if ($country !== null && $taxId !== null) {
            return 'tax:'.strtoupper($country).':'.$taxId;
        }
        foreach (['legal_name', 'trade_name'] as $field) {
            if (($name = $this->fieldKey('name', $company[$field] ?? null)) !== null) {
                return 'name:'.$name;
            }
        }

        return null;
    }

    private function text(string $value): string
    {
        return Str::lower(trim(preg_replace('/\s+/u', ' ', $value) ?? $value));
    }

    private function string(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /** @param list<string> $phones @return list<string> */
    private function phoneDigits(array $phones): array
    {
        return array_values(array_unique(array_map(fn (string $phone): string => ltrim(PhoneNormalizer::normalize($phone), '+'), $phones)));
    }
}
