<?php

namespace App\Http\Requests\Companies;

use App\Models\Company;
use Closure;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends StoreCompanyRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('company'));
    }

    public function rules(): array
    {
        /** @var Company $company */
        $company = $this->route('company');

        $rules = $this->companyRules((int) $company->getKey());
        $rules['tax_id_country'] = ['nullable', 'string', 'size:2', 'regex:/^[A-Z]{2}$/', function (string $attribute, mixed $value, Closure $fail) use ($company): void {
            $taxId = $this->input('tax_id');

            $preservesLegacyPair = $company->tax_id_country === null && $taxId === $company->tax_id;

            if ($taxId !== null && $value === null && ! $preservesLegacyPair) {
                $fail('Informe o país do identificador fiscal.');
            }
        }];
        $rules['assigned_user_id'] = [
            'nullable',
            'integer',
            Rule::exists('users', 'id')->where(function ($query) use ($company): void {
                $query->where('active', true);

                if ($company->assigned_user_id !== null) {
                    $query->orWhere('id', $company->assigned_user_id);
                }
            }),
        ];

        return $rules;
    }
}
