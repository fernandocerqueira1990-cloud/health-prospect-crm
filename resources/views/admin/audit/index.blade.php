<x-layouts.app title="Auditoria">
    <x-page-header title="Auditoria" description="Eventos administrativos e de autenticação registrados pelo backend." />
    <form method="GET" class="card mb-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div><label class="label" for="action">Ação</label><select class="input" id="action" name="action"><option value="">Todas</option>@foreach($actions as $action)<option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>@endforeach</select></div>
        <div><label class="label" for="user_id">Usuário</label><select class="input" id="user_id" name="user_id"><option value="">Todos</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>{{ $user->name }}</option>@endforeach</select></div>
        <div><label class="label" for="from">De</label><input class="input" id="from" name="from" type="date" value="{{ request('from') }}"></div><div><label class="label" for="to">Até</label><input class="input" id="to" name="to" type="date" value="{{ request('to') }}"></div>
        <div class="flex items-end"><button class="btn-primary w-full">Filtrar</button></div>
    </form>
    <div class="table-wrap"><table class="table"><thead><tr><th>Data</th><th>Ação</th><th>Usuário</th><th>Alvo</th><th>Alterações</th><th>IP</th></tr></thead><tbody>
        @forelse($logs as $log)<tr><td class="whitespace-nowrap">{{ $log->created_at?->format('d/m/Y H:i:s') }}</td><td><code>{{ $log->action }}</code></td><td>{{ $log->user?->name ?? 'Sistema/Anônimo' }}</td><td>{{ $log->auditable_type ? class_basename($log->auditable_type).' #'.$log->auditable_id : '—' }}</td><td class="max-w-sm"><details><summary class="cursor-pointer text-teal-700">Ver dados</summary><pre class="mt-2 overflow-auto rounded bg-slate-950 p-3 text-xs text-slate-100">{{ json_encode(['before' => $log->before, 'after' => $log->after], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></details></td><td>{{ $log->ip_address ?? '—' }}</td></tr>
        @empty<tr><td colspan="6" class="text-center">Nenhum evento encontrado.</td></tr>@endforelse
    </tbody></table></div><div class="mt-5">{{ $logs->links() }}</div>
</x-layouts.app>
