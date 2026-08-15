<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Contact;
use App\Models\DataImport;
use App\Models\ImportRow;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Crypt;

class ImportDedupViewData
{
    /** @return array{rows: LengthAwarePaginator<int, ImportRow>, summary: array<string, int>, analyzed_at: string|null} */
    public function build(DataImport $dataImport, User $user): array
    {
        $rows = $dataImport->rows()->whereNotNull('dedup_data')->orderBy('row_number')->paginate(25)->withQueryString();
        $ids = ['company' => [], 'contact' => [], 'lead' => []];
        foreach ($rows as $row) {
            foreach (($row->dedup_data['groups'] ?? []) as $group => $groupData) {
                foreach (($groupData['candidates'] ?? []) as $candidate) {
                    if (($candidate['source'] ?? null) === 'crm' && isset($candidate['id'])) {
                        $ids[$group][] = (int) $candidate['id'];
                    }
                }
            }
        }
        $visible = [
            'company' => $user->can('viewAny', Company::class) ? Company::withTrashed()->whereKey($ids['company'])->get()->keyBy('id') : collect(),
            'contact' => $user->can('viewAny', Contact::class) ? Contact::withTrashed()->with('company')->whereKey($ids['contact'])->get()->keyBy('id') : collect(),
            'lead' => $user->can('viewAny', Lead::class) ? Lead::withTrashed()->whereKey($ids['lead'])->get()->keyBy('id') : collect(),
        ];
        foreach ($rows as $row) {
            $dedup = $row->dedup_data;
            foreach (($dedup['groups'] ?? []) as $group => &$groupData) {
                foreach (($groupData['candidates'] ?? []) as &$candidate) {
                    $candidateId = (int) $candidate[$candidate['source'] === 'crm' ? 'id' : 'import_row_id'];
                    $candidate['decision_ref'] = Crypt::encryptString(json_encode(['source' => $candidate['source'], 'entity' => $group, 'id' => $candidateId], JSON_THROW_ON_ERROR));
                    if (($candidate['source'] ?? null) === 'crm') {
                        $model = $visible[$group]->get($candidateId);
                        $candidate['display'] = $model === null ? ['restricted' => true] : $this->display($group, $model);
                    }
                }
            }
            unset($groupData, $candidate);
            $row->setAttribute('dedup_data', $dedup);
        }

        return [
            'rows' => $rows,
            'summary' => $dataImport->metadata['dedup']['summary'] ?? ['total' => $dataImport->total_rows, 'clear' => 0, 'review' => 0, 'resolved' => 0, 'blocked' => 0, 'exact_matches' => 0, 'possible_matches' => 0],
            'analyzed_at' => $dataImport->metadata['dedup']['analyzed_at'] ?? null,
        ];
    }

    /** @return array<string, mixed> */
    private function display(string $group, Company|Contact|Lead $model): array
    {
        $base = ['restricted' => false, 'archived' => $model->trashed()];

        return $base + match ($group) {
            'company' => ['name' => $model->trade_name ?: $model->legal_name, 'tax_id' => $model->formattedTaxId(), 'location' => implode(' / ', array_filter([$model->city, $model->state]))],
            'contact' => ['name' => $model->name, 'job_title' => $model->job_title, 'email' => $model->email, 'company' => $model->company?->trade_name ?: $model->company?->legal_name],
            'lead' => ['name' => $model->name, 'company' => $model->company_name, 'email' => $model->email, 'phone' => $model->phone, 'status' => $model->status],
            default => throw new \LogicException('Unsupported dedup group.'),
        };
    }
}
