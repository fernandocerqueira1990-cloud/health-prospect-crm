<?php

namespace App\Http\Requests\Imports;

use App\Models\DataImport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rules\File;

class StoreImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', DataImport::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                File::types(['csv'])->max((int) config('imports.max_upload_kb')),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $value instanceof UploadedFile || strtolower($value->getClientOriginalExtension()) !== 'csv') {
                        $fail('O arquivo deve possuir a extensão .csv.');
                    }
                },
            ],
        ];
    }
}
