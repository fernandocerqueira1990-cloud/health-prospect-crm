<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Users\CreateUserAction;
use App\Actions\Users\UpdateUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', User::class);

        return view('admin.users.index', ['users' => User::with('roles')->latest()->paginate(15)]);
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('admin.users.create', ['roles' => $this->manageableRoles()]);
    }

    public function store(StoreUserRequest $request, CreateUserAction $action): RedirectResponse
    {
        $action->execute($request->validated());

        return redirect()->route('admin.users.index')->with('status', __('Usuário criado com sucesso.'));
    }

    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        return view('admin.users.edit', ['managedUser' => $user->load('roles'), 'roles' => $this->manageableRoles()]);
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUserAction $action): RedirectResponse
    {
        $action->execute($user, $request->validated());

        return redirect()->route('admin.users.index')->with('status', __('Usuário atualizado com sucesso.'));
    }

    private function manageableRoles()
    {
        return Role::query()
            ->where('active', true)
            ->when(! request()->user()->hasRole(Role::ADMIN_SLUG), fn ($query) => $query
                ->where('slug', '!=', Role::ADMIN_SLUG)
                ->whereDoesntHave('permissions', fn ($permissions) => $permissions->whereIn('slug', Permission::ADMINISTRATIVE_SLUGS)))
            ->orderBy('name')
            ->get();
    }
}
