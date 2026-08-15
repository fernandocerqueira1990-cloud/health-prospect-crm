<?php

namespace App\Http\Requests\Tasks;

use App\Models\Task;

class UpdateTaskRequest extends StoreTaskRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task instanceof Task
            && $this->user()->can('update', $task);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $task = $this->route('task');

        return $this->taskRules(
            $task instanceof Task
                ? $task
                : null,
        );
    }
}
