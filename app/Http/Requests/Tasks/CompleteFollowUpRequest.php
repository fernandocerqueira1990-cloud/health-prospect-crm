<?php

namespace App\Http\Requests\Tasks;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;

class CompleteFollowUpRequest extends FormRequest
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
        return [
            'outcome' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}
