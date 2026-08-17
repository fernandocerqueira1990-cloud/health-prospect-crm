<?php

namespace App\Http\Requests\Users;

use App\Models\User;
use App\Support\EmailNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => EmailNormalizer::normalize((string) $this->input('email'))]);
        }

        if ($this->has('role_ids')) {
            $this->merge([
                'role_ids' => array_values(array_filter(
                    (array) $this->input('role_ids'),
                    fn (mixed $roleId): bool => $roleId !== null && $roleId !== '',
                )),
            ]);
        }
    }

    public function authorize(): bool
    {
        $user = $this->user();

        return $user->can('create', User::class)
            && (! $this->has('role_ids') || $user->can('users.manage_roles'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'active' => ['required', 'boolean'],
            'role_ids' => ['sometimes', 'array'],
            'role_ids.*' => ['integer', 'distinct', 'exists:roles,id'],
        ];
    }
}
