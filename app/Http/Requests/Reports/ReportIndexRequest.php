<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;

class ReportIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reports.view');
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'date_from' => 'data inicial',
            'date_to' => 'data final',
        ];
    }
}
