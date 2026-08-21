<?php

namespace App\Http\Requests\Roles;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('permission_ids')) {
            $this->merge([
                'permission_ids' => array_values(array_filter(
                    (array) $this->input('permission_ids'),
                    fn (mixed $permissionId): bool => $permissionId !== null && $permissionId !== '',
                )),
            ]);
        }
    }

    public function authorize(): bool
    {
        $user = $this->user();

        return $user->can('update', $this->route('role'))
            && (! $this->has('permission_ids') || $user->can('roles.manage_permissions'))
            && ! $this->grantsAdministrativePermissionsWithoutBeingAdministrator();
    }

    public function rules(): array
    {
        /** @var Role $role */
        $role = $this->route('role');
        $isAdministrator = $role->slug === Role::ADMIN_SLUG;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:255', 'regex:/^[a-z0-9_]+$/',
                $isAdministrator ? Rule::in([Role::ADMIN_SLUG]) : Rule::notIn([Role::ADMIN_SLUG]),
                Rule::unique('roles', 'slug')->ignore($role->getKey()),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'active' => $isAdministrator
                ? ['required', 'boolean', Rule::in([true, 1, '1'])]
                : ['required', 'boolean'],
            'permission_ids' => ['sometimes', 'array'],
            'permission_ids.*' => ['integer', 'distinct', 'exists:permissions,id'],
        ];
    }

    private function grantsAdministrativePermissionsWithoutBeingAdministrator(): bool
    {
        return ! $this->user()->hasRole(Role::ADMIN_SLUG)
            && Permission::query()
                ->whereIn('slug', Permission::ADMINISTRATIVE_SLUGS)
                ->whereKey((array) $this->input('permission_ids', []))
                ->exists();
    }
}
