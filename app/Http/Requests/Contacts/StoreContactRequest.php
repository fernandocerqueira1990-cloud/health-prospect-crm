<?php

namespace App\Http\Requests\Contacts;

use App\Http\Requests\Contacts\Concerns\NormalizesContactInput;
use App\Models\Contact;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContactRequest extends FormRequest
{
    use NormalizesContactInput;

    protected function prepareForValidation(): void
    {
        $this->normalizeContactInput();
    }

    public function authorize(): bool
    {
        return $this->user()->can('create', Contact::class);
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', Rule::exists('companies', 'id')->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64', 'regex:/^\+?\d{6,20}$/'],
            'whatsapp' => ['nullable', 'string', 'max:64', 'regex:/^\+?\d{6,20}$/'],
            'linkedin_url' => ['nullable', 'url:http,https', 'max:2048', function (string $attribute, mixed $value, Closure $fail): void {
                if (is_string($value) && ! preg_match('/(^|\.)linkedin\.com$/i', (string) parse_url($value, PHP_URL_HOST))) {
                    $fail('Informe uma URL válida do LinkedIn.');
                }
            }],
            'decision_role' => ['nullable', Rule::in(Contact::DECISION_ROLES)],
            'influence_level' => ['nullable', Rule::in(Contact::INFLUENCE_LEVELS)],
            'is_primary' => ['required', 'boolean'],
            'active' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
