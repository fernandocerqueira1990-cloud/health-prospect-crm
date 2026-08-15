<?php

namespace App\Http\Requests\Activities;

use App\Models\Activity;

class UpdateActivityRequest extends StoreActivityRequest
{
    public function authorize(): bool
    {
        $activity = $this->route('activity');

        return $activity instanceof Activity
            && $this->user()->can('update', $activity);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $activity = $this->route('activity');

        return $this->activityRules(
            $activity instanceof Activity
                ? $activity
                : null,
        );
    }
}
