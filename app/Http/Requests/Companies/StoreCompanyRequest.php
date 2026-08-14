<?php

namespace App\Http\Requests\Companies;

use App\Http\Requests\Companies\Concerns\NormalizesCompanyInput;
use App\Models\Company;
use App\Support\TaxIdNormalizer;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyRequest extends FormRequest
{
    use NormalizesCompanyInput;

    protected function prepareForValidation(): void
    {
        $this->normalizeCompanyInput();
    }

    public function authorize(): bool
    {
        return $this->user()->can('create', Company::class);
    }

    public function rules(): array
    {
        return $this->companyRules();
    }

    /** @return array<string, mixed> */
    protected function companyRules(?int $ignoreCompanyId = null): array
    {
        $country = $this->input('tax_id_country');
        $taxIdUnique = Rule::unique('companies', 'tax_id')->where('tax_id_country', $country);

        if ($ignoreCompanyId !== null) {
            $taxIdUnique->ignore($ignoreCompanyId);
        }

        return [
            'legal_name' => ['required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'tax_id_country' => ['nullable', 'required_with:tax_id', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'],
            'tax_id' => ['nullable', 'string', 'max:64', $taxIdUnique, function (string $attribute, mixed $value, Closure $fail): void {
                if (! is_string($value)) {
                    return;
                }

                if ($this->input('tax_id_country') === 'BR') {
                    if (preg_match('/^\d{14}$/', $value) !== 1 || ! TaxIdNormalizer::isValidCnpj($value)) {
                        $fail('O CNPJ informado é inválido.');
                    }

                    return;
                }

                if (preg_match('/^[\pL\pN][\pL\pN.\-\/ ]*$/u', $value) !== 1) {
                    $fail('O identificador fiscal possui caracteres inválidos.');
                }
            }],
            'segment' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url:http,https', 'max:2048'],
            'phone' => ['nullable', 'string', 'max:64'],
            'email' => ['nullable', 'email', 'max:255'],
            'street' => ['nullable', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:32'],
            'complement' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'employee_count_estimate' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            'assigned_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('active', true)],
            'priority' => ['nullable', Rule::in(Company::PRIORITIES)],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
