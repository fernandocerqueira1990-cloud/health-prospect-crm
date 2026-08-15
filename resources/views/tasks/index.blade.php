<x-layouts.app title="Tarefas">
    <x-page-header
        title="Tarefas"
        description="Pendências, responsáveis, prioridades e próximos passos."
    >
        <x-slot:actions>
            @can('create', App\Models\Task::class)
                <a class="btn-primary" href="{{ route('tasks.create') }}">
                    Nova tarefa
                </a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <form
        class="card mb-6"
        method="GET"
        action="{{ route('tasks.index') }}"
    >
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="sm:col-span-2">
                <label class="label" for="q">Busca geral</label>

                <input
                    class="input"
                    id="q"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Tarefa, empresa, contato, lead..."
                >
            </div>

            <div>
                <label class="label" for="status">Status</label>

                <select class="input" id="status" name="status">
                    <option value="">Todos</option>
                    <option value="pending" @selected(request('status') === 'pending')>
                        Pendente
                    </option>
                    <option value="in_progress" @selected(request('status') === 'in_progress')>
                        Em andamento
                    </option>
                    <option value="completed" @selected(request('status') === 'completed')>
                        Concluída
                    </option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>
                        Cancelada
                    </option>
                </select>
            </div>

            <div>
                <label class="label" for="priority">Prioridade</label>

                <select class="input" id="priority" name="priority">
                    <option value="">Todas</option>
                    <option value="low" @selected(request('priority') === 'low')>
                        Baixa
                    </option>
                    <option value="medium" @selected(request('priority') === 'medium')>
                        Média
                    </option>
                    <option value="high" @selected(request('priority') === 'high')>
                        Alta
                    </option>
                    <option value="urgent" @selected(request('priority') === 'urgent')>
                        Urgente
                    </option>
                </select>
            </div>

            <div>
                <label class="label" for="assigned_user_id">
                    Responsável
                </label>

                <select
                    class="input"
                    id="assigned_user_id"
                    name="assigned_user_id"
                >
                    <option value="">Todos</option>

                    @foreach($assignedUsers as $user)
                        <option
                            value="{{ $user->id }}"
                            @selected(
                                (string) request('assigned_user_id')
                                === (string) $user->id
                            )
                        >
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="label" for="due_from">Prazo de</label>
                <input
                    class="input"
                    id="due_from"
                    name="due_from"
                    type="date"
                    value="{{ request('due_from') }}"
                >
            </div>

            <div>
                <label class="label" for="due_to">Prazo até</label>
                <input
                    class="input"
                    id="due_to"
                    name="due_to"
                    type="date"
                    value="{{ request('due_to') }}"
                >
            </div>

            <div>
                <label class="label" for="per_page">Por página</label>

                <select class="input" id="per_page" name="per_page">
                    @foreach([15, 30, 50, 100] as $value)
                        <option
                            value="{{ $value }}"
                            @selected(
                                (int) request('per_page', 15) === $value
                            )
                        >
                            {{ $value }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-5 flex flex-wrap gap-3">
            <button class="btn-primary" type="submit">Filtrar</button>

            <a class="btn-secondary" href="{{ route('tasks.index') }}">
                Limpar
            </a>
        </div>
    </form>

    <div class="mb-4 text-sm text-slate-500">
        @if($tasks->total() > 0)
            Mostrando {{ $tasks->firstItem() }}–{{ $tasks->lastItem() }}
            de {{ $tasks->total() }} tarefas
        @else
            Nenhuma tarefa encontrada
        @endif
    </div>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Tarefa</th>
                    <th>Status</th>
                    <th>Prioridade</th>
                    <th>Responsável</th>
                    <th>Prazo</th>
                    <th>Vínculo</th>
                    <th></th>
                </tr>
            </thead>

            <tbody>
                @forelse($tasks as $task)
                    @php
                        $statusLabel = match($task->status) {
                            'pending' => 'Pendente',
                            'in_progress' => 'Em andamento',
                            'completed' => 'Concluída',
                            'cancelled' => 'Cancelada',
                            default => $task->status,
                        };

                        $priorityLabel = match($task->priority) {
                            'low' => 'Baixa',
                            'medium' => 'Média',
                            'high' => 'Alta',
                            'urgent' => 'Urgente',
                            default => $task->priority,
                        };

                        $overdue = $task->due_at
                            && !in_array($task->status, ['completed', 'cancelled'], true)
                            && $task->due_at->isPast();
                    @endphp

                    <tr>
                        <td>
                            <a
                                class="font-semibold text-teal-700 hover:text-teal-900"
                                href="{{ route('tasks.show', $task) }}"
                            >
                                {{ $task->title }}
                            </a>
                        </td>

                        <td>{{ $statusLabel }}</td>

                        <td>
                            <span class="font-semibold">
                                {{ $priorityLabel }}
                            </span>
                        </td>

                        <td>
                            {{ $task->assignedUser?->name ?? 'Não atribuído' }}
                        </td>

                        <td>
                            @if($task->due_at)
                                <span class="{{ $overdue ? 'font-semibold text-red-600' : '' }}">
                                    {{ $task->due_at->format('d/m/Y H:i') }}
                                </span>

                                @if($overdue)
                                    <br>
                                    <span class="text-xs font-semibold text-red-600">
                                        Atrasada
                                    </span>
                                @endif
                            @else
                                —
                            @endif
                        </td>

                        <td>
                            @if($task->company)
                                {{
                                    $task->company->trade_name
                                    ?: $task->company->legal_name
                                }}
                            @elseif($task->opportunity)
                                {{ $task->opportunity->title }}
                            @elseif($task->lead)
                                {{
                                    $task->lead->name
                                    ?: $task->lead->company_name
                                    ?: 'Lead #'.$task->lead->id
                                }}
                            @elseif($task->contact)
                                {{ $task->contact->name }}
                            @else
                                Interna
                            @endif
                        </td>

                        <td class="text-right">
                            <div class="flex justify-end gap-3">
                                @can('view', $task)
                                    <a
                                        class="font-semibold text-teal-700"
                                        href="{{ route('tasks.show', $task) }}"
                                    >
                                        Ver
                                    </a>
                                @endcan

                                @can('update', $task)
                                    <a
                                        class="font-semibold text-teal-700"
                                        href="{{ route('tasks.edit', $task) }}"
                                    >
                                        Editar
                                    </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">
                            Nenhuma tarefa encontrada.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">
        {{ $tasks->links() }}
    </div>
</x-layouts.app>
