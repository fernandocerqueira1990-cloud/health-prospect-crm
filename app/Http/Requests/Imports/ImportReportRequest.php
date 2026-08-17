<?php

namespace App\Http\Requests\Imports;

use App\Models\DataImport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $dataImport = $this->route('dataImport');

        return $dataImport instanceof DataImport && $this->user()->can('view', $dataImport);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['status' => ['nullable', Rule::in(['all', 'success', 'reused', 'skipped', 'failed', 'blocked'])], 'page' => ['nullable', 'integer', 'min:1']];
    }
}
