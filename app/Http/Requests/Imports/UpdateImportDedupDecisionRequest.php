<?php

namespace App\Http\Requests\Imports;

use App\Models\DataImport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateImportDedupDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $dataImport = $this->route('dataImport');

        return $dataImport instanceof DataImport && $this->user()->can('update', $dataImport);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'group' => ['required', Rule::in(['company', 'contact', 'lead'])],
            'action' => ['required', Rule::in(['create_new', 'use_existing', 'reuse_import_row', 'skip'])],
            'candidate_ref' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
