<x-layouts.app title="Roles">
    <x-page-header title="Roles" description="Perfis de acesso e suas permissões."><x-slot:actions>@can('create', App\Models\Role::class)<a class="btn-primary" href="{{ route('admin.roles.create') }}">Nova role</a>@endcan</x-slot:actions></x-page-header>
    <div class="table-wrap"><table class="table"><thead><tr><th>Role</th><th>Slug</th><th>Usuários</th><th>Permissões</th><th>Estado</th><th></th></tr></thead><tbody>
    @foreach($roles as $role)<tr><td><span class="font-semibold text-slate-900">{{ $role->name }}</span><br><span class="text-slate-500">{{ $role->description }}</span></td><td><code>{{ $role->slug }}</code></td><td>{{ $role->users_count }}</td><td>{{ $role->permissions_count }}</td><td>{{ $role->active ? 'Ativa' : 'Inativa' }}</td><td class="text-right">@can('update', $role)<a class="font-semibold text-teal-700" href="{{ route('admin.roles.edit', $role) }}">Editar</a>@endcan</td></tr>@endforeach
    </tbody></table></div><div class="mt-5">{{ $roles->links() }}</div>
</x-layouts.app>
