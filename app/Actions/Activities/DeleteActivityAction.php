<?php

namespace App\Actions\Activities;

use App\Models\Activity;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class DeleteActivityAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function execute(Activity $activity): void
    {
        DB::transaction(function () use ($activity): void {
            $before = $activity->attributesToArray();

            $activity->delete();

            $this->audit->record(
                'activity_deleted',
                $activity,
                before: $before,
            );
        });
    }
}
