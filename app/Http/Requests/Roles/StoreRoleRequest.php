<?php

namespace App\Http\Requests\Roles;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
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

        return $user->can('create', Role::class)
            && (! $this->has('permission_ids') || $user->can('roles.manage_permissions'))
            && ! $this->grantsAdministrativePermissionsWithoutBeingAdministrator();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9_]+$/', Rule::notIn([Role::ADMIN_SLUG]), 'unique:roles,slug'],
            'description' => ['nullable', 'string', 'max:1000'],
            'active' => ['required', 'boolean'],
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
