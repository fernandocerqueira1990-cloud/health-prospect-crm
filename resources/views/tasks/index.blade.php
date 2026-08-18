@php
    $statusLabels = ['pending' => 'Pendente', 'in_progress' => 'Em andamento', 'completed' => 'Concluída', 'cancelled' => 'Cancelada'];
    $statusVariants = ['pending' => 'warning', 'in_progress' => 'info', 'completed' => 'success', 'cancelled' => 'neutral'];
    $priorityLabels = ['low' => 'Baixa', 'medium' => 'Média', 'high' => 'Alta', 'urgent' => 'Urgente'];
    $priorityVariants = ['low' => 'neutral', 'medium' => 'info', 'high' => 'warning', 'urgent' => 'danger'];
    $advancedFilters = ['priority', 'due_from', 'due_to', 'per_page'];
    $hasAdvancedFilters = collect($advancedFilters)->contains(fn ($filter) => request()->filled($filter));
    $hasFilters = request()->except('page') !== [];
@endphp

<x-layouts.app title="Tarefas">
    <x-page-header title="Tarefas" description="Pendências e compromissos da operação comercial.">
        <x-slot:actions>
            @can('create', App\Models\Task::class)
                <a class="btn-primary" href="{{ route('tasks.create') }}">Nova tarefa</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <form class="listing-filters" method="GET" action="{{ route('tasks.index') }}">
        <x-filter-bar class="mb-0 border-0 p-0 shadow-none">
            <div class="filter-field-primary">
                <label class="label" for="q">Busca geral</label>
                <input class="input" id="q" name="q" value="{{ request('q') }}" placeholder="Tarefa, empresa, contato, lead...">
            </div>
            <div class="filter-field-select">
                <label class="label" for="status">Status</label>
                <select class="input" id="status" name="status">
                    <option value="">Todos</option>
                    @foreach($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field-select">
                <label class="label" for="assigned_user_id">Responsável</label>
                <select class="input" id="assigned_user_id" name="assigned_user_id">
                    <option value="">Todos</option>
                    @foreach($assignedUsers as $user)
                        <option value="{{ $user->id }}" @selected((string) request('assigned_user_id') === (string) $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <x-slot:actions><button class="btn-primary" type="submit">Filtrar</button></x-slot:actions>
        </x-filter-bar>

        <div class="listing-filter-footer">
            <details class="advanced-filters" @if($hasAdvancedFilters) open @endif>
                <summary>Filtros avançados</summary>
                <div class="advanced-filter-grid">
                    <div>
                        <label class="label" for="priority">Prioridade</label>
                        <select class="input" id="priority" name="priority">
                            <option value="">Todas</option>
                            @foreach($priorityLabels as $value => $label)
                                <option value="{{ $value }}" @selected(request('priority') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label class="label" for="due_from">Prazo de</label><input class="input" id="due_from" name="due_from" type="date" value="{{ request('due_from') }}"></div>
                    <div><label class="label" for="due_to">Prazo até</label><input class="input" id="due_to" name="due_to" type="date" value="{{ request('due_to') }}"></div>
                    <div>
                        <label class="label" for="per_page">Por página</label>
                        <select class="input" id="per_page" name="per_page">
                            @foreach([15, 30, 50, 100] as $value)
                                <option value="{{ $value }}" @selected((int) request('per_page', 15) === $value)>{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </details>
            @if($hasFilters)<a class="filter-clear" href="{{ route('tasks.index') }}">Limpar filtros</a>@endif
        </div>
    </form>

    <div class="listing-summary">
        @if($tasks->total())
            Mostrando {{ $tasks->firstItem() }}–{{ $tasks->lastItem() }} de {{ $tasks->total() }} tarefas
        @else
            Nenhuma tarefa encontrada
        @endif
    </div>

    <div class="table-wrap">
        <table class="table listing-table">
            <thead><tr><th>Tarefa</th><th>Vínculo</th><th>Prioridade</th><th>Responsável</th><th>Vencimento</th><th>Status</th><th class="text-right">AÇÕES</th></tr></thead>
            <tbody>
                @forelse($tasks as $task)
                    @php
                        $isClosed = in_array($task->status, ['completed', 'cancelled'], true);
                        $dueVariant = 'neutral';
                        $dueTextClass = 'text-slate-800';
                        $dueLabel = null;
                        if ($task->due_at) {
                            if ($isClosed) {
                                $dueTextClass = 'text-slate-500';
                                $dueLabel = $task->status === 'completed' ? 'Concluída' : 'Cancelada';
                            } elseif ($task->due_at->isToday()) {
                                $dueVariant = 'warning';
                                $dueTextClass = 'text-amber-800';
                                $dueLabel = 'Hoje';
                            } elseif ($task->due_at->isPast()) {
                                $dueVariant = 'danger';
                                $dueTextClass = 'text-red-700';
                                $dueLabel = 'Atrasada';
                            } else {
                                $dueVariant = 'info';
                                $dueTextClass = 'text-sky-700';
                            }
                        }
                    @endphp
                    <tr class="{{ $isClosed ? 'text-slate-500' : '' }}">
                        <td><a class="table-primary-link" href="{{ route('tasks.show', $task) }}">{{ $task->title }}</a></td>
                        <td>
                            @if($task->company)
                                <span class="font-medium text-slate-800">{{ $task->company->trade_name ?: $task->company->legal_name }}</span><span class="table-secondary-line">Empresa</span>
                            @elseif($task->opportunity)
                                <span class="font-medium text-slate-800">{{ $task->opportunity->title }}</span><span class="table-secondary-line">Oportunidade</span>
                            @elseif($task->lead)
                                <span class="font-medium text-slate-800">{{ $task->lead->name ?: $task->lead->company_name ?: 'Lead #'.$task->lead->id }}</span><span class="table-secondary-line">Lead</span>
                            @elseif($task->contact)
                                <span class="font-medium text-slate-800">{{ $task->contact->name }}</span><span class="table-secondary-line">Contato</span>
                            @else
                                <span class="text-slate-500">Interna</span>
                            @endif
                        </td>
                        <td><x-status-badge :variant="$priorityVariants[$task->priority] ?? 'neutral'">{{ $priorityLabels[$task->priority] ?? $task->priority }}</x-status-badge></td>
                        <td>{{ $task->assignedUser?->name ?? 'Não atribuído' }}</td>
                        <td class="whitespace-nowrap">
                            @if($task->due_at)
                                <span class="font-medium {{ $dueTextClass }}">{{ $task->due_at->format('d/m/Y H:i') }}</span>
                                @if($dueLabel)<span class="table-secondary-line"><x-status-badge :variant="$dueVariant">{{ $dueLabel }}</x-status-badge></span>@endif
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td><x-status-badge :variant="$statusVariants[$task->status] ?? 'neutral'">{{ $statusLabels[$task->status] ?? $task->status }}</x-status-badge></td>
                        <td><div class="table-actions">@can('view', $task)<a class="table-action-link" href="{{ route('tasks.show', $task) }}">Ver</a>@endcan @can('update', $task)<a class="table-action-link" href="{{ route('tasks.edit', $task) }}">Editar</a>@endcan</div></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty-state :title="$hasFilters ? 'Nenhuma tarefa encontrada.' : 'Nenhuma tarefa cadastrada ainda.'" :description="$hasFilters ? 'Revise ou limpe os filtros para ampliar os resultados.' : 'As tarefas cadastradas aparecerão nesta lista.'" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($tasks->hasPages())<div class="listing-pagination">{{ $tasks->links() }}</div>@endif
</x-layouts.app>
