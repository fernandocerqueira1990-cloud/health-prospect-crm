<?php

namespace App\Http\Requests\Contacts;

use App\Models\Contact;
use App\Support\PhoneNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContactIndexRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $values = [];
        foreach (['search', 'name', 'job_title', 'department', 'email', 'phone'] as $field) {
            if ($this->has($field)) {
                $value = trim((string) $this->input($field));
                $values[$field] = $value === '' ? null : $value;
            }
        }
        if (isset($values['phone'])) {
            $phone = PhoneNormalizer::normalize($values['phone']);
            $values['phone'] = $phone === '' ? null : $phone;
        }
        $this->merge($values);
    }

    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Contact::class);
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'integer', 'exists:companies,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'decision_role' => ['nullable', Rule::in(Contact::DECISION_ROLES)],
            'influence_level' => ['nullable', Rule::in(Contact::INFLUENCE_LEVELS)],
            'is_primary' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(['name', 'job_title', 'department', 'created_at', 'updated_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }
}
