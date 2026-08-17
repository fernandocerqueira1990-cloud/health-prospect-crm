<?php

namespace App\Actions\Activities;

use App\Models\Activity;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class UpdateActivityAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    /** @param array<string, mixed> $data */
    public function execute(Activity $activity, array $data): Activity
    {
        return DB::transaction(function () use ($activity, $data): Activity {
            $before = $activity->attributesToArray();

            $activity->update($data);
            $activity->refresh();

            $this->audit->record(
                'activity_updated',
                $activity,
                before: $before,
                after: $activity->attributesToArray(),
            );

            return $activity;
        });
    }
}
