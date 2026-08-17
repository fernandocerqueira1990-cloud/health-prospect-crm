<?php

namespace App\Http\Requests\Imports;

use App\Models\DataImport;
use Illuminate\Foundation\Http\FormRequest;

class AnalyzeImportDedupRequest extends FormRequest
{
    public function authorize(): bool
    {
        $dataImport = $this->route('dataImport');

        return $dataImport instanceof DataImport && $this->user()->can('update', $dataImport);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
