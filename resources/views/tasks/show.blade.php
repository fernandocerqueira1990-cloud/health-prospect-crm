<x-layouts.app title="{{ $task->title }}">
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
    @endphp

    <x-page-header
        title="{{ $task->title }}"
        description="{{ $statusLabel }} · Prioridade {{ $priorityLabel }}"
    >
        <x-slot:actions>
            @can('update', $task)
                <a
                    class="btn-primary"
                    href="{{ route('tasks.edit', $task) }}"
                >
                    Editar
                </a>
            @endcan

            <a class="btn-secondary" href="{{ route('tasks.index') }}">
                Voltar
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="card">
                <h2 class="text-lg font-semibold text-slate-950">
                    Detalhes
                </h2>

                <dl class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Status
                        </dt>
                        <dd class="mt-1">{{ $statusLabel }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Prioridade
                        </dt>
                        <dd class="mt-1">{{ $priorityLabel }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Prazo
                        </dt>
                        <dd class="mt-1">
                            {{ $task->due_at?->format('d/m/Y H:i') ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Responsável
                        </dt>
                        <dd class="mt-1">
                            {{ $task->assignedUser?->name ?? 'Não atribuído' }}
                        </dd>
                    </div>
                </dl>

                @if($task->description)
                    <div class="mt-6 border-t border-slate-200 pt-5">
                        <p class="text-xs font-semibold uppercase text-slate-500">
                            Descrição
                        </p>

                        <p class="mt-2 whitespace-pre-line text-slate-800">
                            {{ $task->description }}
                        </p>
                    </div>
                @endif
            </section>

            <section class="card">
                <h2 class="text-lg font-semibold text-slate-950">
                    Vínculos comerciais
                </h2>

                <dl class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Empresa
                        </dt>
                        <dd class="mt-1">
                            {{ $task->company?->trade_name ?? $task->company?->legal_name ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Contato
                        </dt>
                        <dd class="mt-1">
                            {{ $task->contact?->name ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Lead
                        </dt>
                        <dd class="mt-1">
                            {{ $task->lead?->name ?? $task->lead?->company_name ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Oportunidade
                        </dt>
                        <dd class="mt-1">
                            {{ $task->opportunity?->title ?? '—' }}
                        </dd>
                    </div>
                </dl>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="card">
                <h2 class="text-lg font-semibold text-slate-950">
                    Controle
                </h2>

                <dl class="mt-5 space-y-5">
                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Criada por
                        </dt>
                        <dd class="mt-1">
                            {{ $task->createdByUser?->name ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Iniciada em
                        </dt>
                        <dd class="mt-1">
                            {{ $task->started_at?->format('d/m/Y H:i') ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Concluída em
                        </dt>
                        <dd class="mt-1">
                            {{ $task->completed_at?->format('d/m/Y H:i') ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Cancelada em
                        </dt>
                        <dd class="mt-1">
                            {{ $task->cancelled_at?->format('d/m/Y H:i') ?? '—' }}
                        </dd>
                    </div>
                </dl>
            </section>

            @if(
                $task->is_follow_up
                && ! in_array($task->status, ['completed', 'cancelled'], true)
            )
                @can('update', $task)
                    <section class="card border-teal-200">
                        <h2 class="font-semibold text-teal-800">
                            Concluir follow-up
                        </h2>

                        <p class="mt-2 text-sm text-slate-500">
                            A conclusão criará automaticamente uma atividade no histórico.
                        </p>

                        <form
                            class="mt-4"
                            method="POST"
                            action="{{ route('tasks.complete-follow-up', $task) }}"
                        >
                            @csrf

                            <label class="label" for="outcome">
                                Resultado do contato
                            </label>

                            <textarea
                                class="input min-h-24"
                                id="outcome"
                                name="outcome"
                                maxlength="2000"
                                placeholder="Ex.: Cliente confirmou interesse e solicitou proposta."
                            ></textarea>

                            <button
                                class="btn-primary mt-4"
                                type="submit"
                            >
                                Concluir follow-up
                            </button>
                        </form>
                    </section>
                @endcan
            @endif

            @if($task->completedActivity)
                <section class="card">
                    <h2 class="font-semibold text-slate-950">
                        Atividade gerada
                    </h2>

                    <p class="mt-2 text-sm text-slate-500">
                        Este follow-up já foi concluído e registrado no histórico.
                    </p>

                    <a
                        class="mt-4 inline-block font-semibold text-teal-700"
                        href="{{ route('activities.show', $task->completedActivity) }}"
                    >
                        Abrir atividade →
                    </a>
                </section>
            @endif

            @can('delete', $task)
                <section class="card border-red-200">
                    <h2 class="font-semibold text-red-700">
                        Excluir tarefa
                    </h2>

                    <p class="mt-2 text-sm text-slate-500">
                        O registro será arquivado por soft delete.
                    </p>

                    <form
                        class="mt-4"
                        method="POST"
                        action="{{ route('tasks.destroy', $task) }}"
                        onsubmit="return confirm('Deseja excluir esta tarefa?')"
                    >
                        @csrf
                        @method('DELETE')

                        <button
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700"
                            type="submit"
                        >
                            Excluir
                        </button>
                    </form>
                </section>
            @endcan
        </aside>
    </div>
</x-layouts.app>
