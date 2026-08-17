<x-layouts.app title="Usuários">
    <x-page-header title="Usuários" description="Gerencie contas, acesso e estado dos usuários.">
        <x-slot:actions>@can('create', App\Models\User::class)<a class="btn-primary" href="{{ route('admin.users.create') }}">Novo usuário</a>@endcan</x-slot:actions>
    </x-page-header>
    <div class="table-wrap"><table class="table"><thead><tr><th>Usuário</th><th>Roles</th><th>Estado</th><th>Último login</th><th></th></tr></thead><tbody>
        @forelse($users as $managedUser)<tr>
            <td><span class="font-semibold text-slate-900">{{ $managedUser->name }}</span><br><span class="text-slate-500">{{ $managedUser->email }}</span></td>
            <td>{{ $managedUser->roles->pluck('name')->join(', ') ?: 'Sem role' }}</td>
            <td><span class="rounded-full px-2 py-1 text-xs font-semibold {{ $managedUser->active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">{{ $managedUser->active ? 'Ativo' : 'Inativo' }}</span></td>
            <td>{{ $managedUser->last_login_at?->format('d/m/Y H:i') ?? 'Nunca' }}</td>
            <td class="text-right">@can('update', $managedUser)<a class="font-semibold text-teal-700 hover:text-teal-900" href="{{ route('admin.users.edit', $managedUser) }}">Editar</a>@endcan</td>
        </tr>@empty<tr><td colspan="5" class="text-center">Nenhum usuário cadastrado.</td></tr>@endforelse
    </tbody></table></div><div class="mt-5">{{ $users->links() }}</div>
</x-layouts.app>
