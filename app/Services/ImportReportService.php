<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Contact;
use App\Models\DataImport;
use App\Models\ImportRow;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ImportReportService
{
    /** @return array{rows: LengthAwarePaginator<int, ImportRow>, summary: array<string, mixed>, can_view_ids: array<string, bool>, duration_seconds: int|null, executor_name: string|null} */
    public function build(DataImport $dataImport, string $status, User $user): array
    {
        $query = $dataImport->rows()->select(['id', 'row_number', 'execution_data'])->whereNotNull('execution_data')->orderBy('row_number');
        if ($status !== 'all') {
            $query->where('execution_data->status', $status);
        }
        $duration = $dataImport->started_at !== null && $dataImport->finished_at !== null
            ? (int) $dataImport->started_at->diffInSeconds($dataImport->finished_at)
            : null;

        return [
            'rows' => $query->paginate(50)->withQueryString(),
            'summary' => $dataImport->metadata['execution']['summary'] ?? [],
            'can_view_ids' => [
                'company' => $user->can('viewAny', Company::class),
                'contact' => $user->can('viewAny', Contact::class),
                'lead' => $user->can('viewAny', Lead::class),
            ],
            'duration_seconds' => $duration,
            'executor_name' => User::query()->whereKey($dataImport->metadata['execution']['started_by_user_id'] ?? null)->value('name'),
        ];
    }
}
