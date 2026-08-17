<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Contact;
use App\Models\ImportRow;
use App\Models\Lead;
use Illuminate\Support\Collection;

class ImportExecutionCandidateRegistry
{
    /** @var array<string, array<int, Company|Contact|Lead>> */
    private array $models = ['company' => [], 'contact' => [], 'lead' => []];

    /** @param Collection<int, ImportRow> $rows */
    public function load(Collection $rows): void
    {
        $ids = ['company' => [], 'contact' => [], 'lead' => []];
        foreach ($rows as $row) {
            foreach (($row->dedup_data['groups'] ?? []) as $group => $groupData) {
                $decision = is_array($groupData) ? ($groupData['decision'] ?? null) : null;
                if (isset($ids[$group]) && is_array($decision) && ($decision['action'] ?? null) === 'use_existing' && is_int($decision['candidate_id'] ?? null)) {
                    $ids[$group][] = $decision['candidate_id'];
                }
            }
        }

        $this->models['company'] = Company::withTrashed()->whereKey(array_unique($ids['company']))->get()->keyBy('id')->all();
        $this->models['contact'] = Contact::withTrashed()->whereKey(array_unique($ids['contact']))->get()->keyBy('id')->all();
        $this->models['lead'] = Lead::withTrashed()->whereKey(array_unique($ids['lead']))->get()->keyBy('id')->all();
    }

    public function get(string $group, int $id): Company|Contact|Lead|null
    {
        return $this->models[$group][$id] ?? null;
    }
}
