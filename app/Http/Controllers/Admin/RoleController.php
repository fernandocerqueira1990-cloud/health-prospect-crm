<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Roles\CreateRoleAction;
use App\Actions\Roles\UpdateRoleAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\StoreRoleRequest;
use App\Http\Requests\Roles\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Role::class);

        return view('admin.roles.index', ['roles' => Role::withCount(['users', 'permissions'])->orderBy('name')->paginate(15)]);
    }

    public function create(): View
    {
        Gate::authorize('create', Role::class);

        return view('admin.roles.create', ['permissions' => $this->manageablePermissions()]);
    }

    public function store(StoreRoleRequest $request, CreateRoleAction $action): RedirectResponse
    {
        $action->execute($request->validated());

        return redirect()->route('admin.roles.index')->with('status', __('Role criada com sucesso.'));
    }

    public function edit(Role $role): View
    {
        Gate::authorize('update', $role);

        return view('admin.roles.edit', ['role' => $role->load('permissions'), 'permissions' => $this->manageablePermissions()]);
    }

    public function update(UpdateRoleRequest $request, Role $role, UpdateRoleAction $action): RedirectResponse
    {
        $action->execute($role, $request->validated());

        return redirect()->route('admin.roles.index')->with('status', __('Role atualizada com sucesso.'));
    }

    private function manageablePermissions()
    {
        return Permission::query()
            ->when(! request()->user()->hasRole(Role::ADMIN_SLUG), fn ($query) => $query->whereNotIn('slug', Permission::ADMINISTRATIVE_SLUGS))
            ->orderBy('slug')
            ->get();
    }
}
