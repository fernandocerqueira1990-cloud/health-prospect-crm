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
                File::types(['csv', 'xlsx'])->max((int) config('imports.max_upload_kb')),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $value instanceof UploadedFile || ! in_array(strtolower($value->getClientOriginalExtension()), ['csv', 'xlsx'], true)) {
                        $fail('O arquivo deve possuir a extensão .csv ou .xlsx.');

                        return;
                    }

                    $name = $value->getClientOriginalName();
                    $basename = basename(str_replace('\\', '/', $name));
                    $stem = pathinfo($basename, PATHINFO_FILENAME);
                    if (preg_match('/\.(?:php\d*|phtml|phar|cgi|pl|py|sh|exe|com|bat|cmd|js|html?|svg)$/i', $stem) === 1) {
                        $fail('Arquivos com extensão dupla executável não são permitidos.');
                    }
                },
            ],
        ];
    }
}
