<?php

namespace App\Http\Requests\Users;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\EmailNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
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

        /** @var User $managedUser */
        $managedUser = $this->route('user');

        return $user->can('update', $managedUser)
            && (! $this->has('role_ids') || $user->can('users.manage_roles'))
            && ! $this->assignsAdministratorRoleWithoutBeingAdministrator($user);
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            /** @var User $managedUser */
            $managedUser = $this->route('user');

            if ($this->causesCriticalSelfLockout($this->user(), $managedUser)) {
                $validator->errors()->add('active', 'Sua própria sessão administrativa não pode ser bloqueada por esta operação.');
                $validator->errors()->add('role_ids', 'Sua própria role Administrador não pode ser removida por esta operação.');
            }
        }];
    }

    public function rules(): array
    {
        $userId = $this->route('user')->getKey();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'active' => ['required', 'boolean'],
            'role_ids' => ['sometimes', 'array'],
            'role_ids.*' => ['integer', 'distinct', 'exists:roles,id'],
        ];
    }

    private function causesCriticalSelfLockout(User $actor, User $managedUser): bool
    {
        if (! $actor->is($managedUser)) {
            return false;
        }

        if ($this->has('active') && ! $this->boolean('active')) {
            return true;
        }

        if (! $this->has('role_ids') || ! $actor->hasRole(Role::ADMIN_SLUG)) {
            return false;
        }

        $administratorRoleId = Role::query()->where('slug', Role::ADMIN_SLUG)->value('id');

        return ! in_array((int) $administratorRoleId, array_map('intval', (array) $this->input('role_ids')), true);
    }

    private function assignsAdministratorRoleWithoutBeingAdministrator(User $actor): bool
    {
        return ! $actor->hasRole(Role::ADMIN_SLUG)
            && Role::query()
                ->whereKey((array) $this->input('role_ids', []))
                ->where(fn ($query) => $query
                    ->where('slug', Role::ADMIN_SLUG)
                    ->orWhereHas('permissions', fn ($permissions) => $permissions->whereIn('slug', Permission::ADMINISTRATIVE_SLUGS)))
                ->exists();
    }
}
