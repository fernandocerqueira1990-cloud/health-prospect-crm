<?php

namespace App\Actions\Activities;

use App\Models\Activity;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class CreateActivityAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    /** @param array<string, mixed> $data */
    public function execute(array $data, User $user): Activity
    {
        return DB::transaction(function () use ($data, $user): Activity {
            $data['created_by_user_id'] = $user->id;

            if (empty($data['assigned_user_id'])) {
                $data['assigned_user_id'] = $user->id;
            }

            $activity = Activity::create($data);

            $this->audit->record(
                'activity_created',
                $activity,
                after: $activity->attributesToArray(),
            );

            return $activity;
        });
    }
}
