<?php

namespace App\Http\Requests\Imports;

use App\Models\DataImport;
use Illuminate\Foundation\Http\FormRequest;

class UpdateImportMappingRequest extends FormRequest
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
            'columns' => ['required', 'array', 'min:1'],
            'columns.*' => ['required', 'array:source,target'],
            'columns.*.source' => ['required', 'string', 'max:255', 'distinct:strict'],
            'columns.*.target' => ['nullable', 'string', 'max:100'],
        ];
    }
}
