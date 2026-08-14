<?php

namespace App\Http\Requests\Companies;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyIndexRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (['search', 'legal_name', 'trade_name', 'tax_id', 'segment', 'category', 'city', 'state', 'district'] as $field) {
            if ($this->has($field)) {
                $value = trim((string) $this->input($field));
                $normalized[$field] = $value === '' ? null : $value;
            }
        }

        $this->merge($normalized);
    }

    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Company::class);
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:64'],
            'segment' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:255'],
            'assigned_user' => ['nullable', 'integer', 'exists:users,id'],
            'priority' => ['nullable', Rule::in(Company::PRIORITIES)],
            'created_from' => ['nullable', 'date_format:Y-m-d'],
            'created_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:created_from'],
            'sort' => ['nullable', Rule::in(['legal_name', 'trade_name', 'city', 'state', 'priority', 'created_at', 'updated_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }
}
