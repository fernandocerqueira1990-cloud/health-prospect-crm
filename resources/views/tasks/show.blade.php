<x-layouts.app title="{{ $task->title }}">
    @php
        $statusLabel = match($task->status) { 'pending' => 'Pendente', 'in_progress' => 'Em andamento', 'completed' => 'Concluída', 'cancelled' => 'Cancelada', default => $task->status };
        $priorityLabel = match($task->priority) { 'low' => 'Baixa', 'medium' => 'Média', 'high' => 'Alta', 'urgent' => 'Urgente', default => $task->priority };
        $statusVariant = match($task->status) { 'completed' => 'success', 'in_progress' => 'info', 'cancelled' => 'danger', default => 'warning' };
        $priorityVariant = match($task->priority) { 'urgent' => 'danger', 'high' => 'warning', 'low' => 'info', default => 'neutral' };
    @endphp

    <x-page-header :title="$task->title" description="Detalhes da pendência ou compromisso.">
        <x-slot:actions>
            @can('update', $task)<a class="btn-primary" href="{{ route('tasks.edit', $task) }}">Editar</a>@endcan
            <a class="btn-secondary" href="{{ route('tasks.index') }}">Voltar</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 xl:grid-cols-3">
        <div class="space-y-4 xl:col-span-2">
            <x-form-section title="Identificação">
                <dl class="detail-grid"><div class="detail-item sm:col-span-2"><dt class="detail-label">Título</dt><dd class="detail-value break-words">{{ $task->title }}</dd></div></dl>
                <div class="mt-4 border-t border-slate-100 pt-4"><p class="detail-label">Descrição</p><p class="mt-1 whitespace-pre-line break-words text-sm leading-6 text-slate-700">{{ $task->description ?? 'Nenhuma descrição registrada.' }}</p></div>
            </x-form-section>

            <x-form-section title="Vínculos comerciais">
                <dl class="detail-grid lg:grid-cols-4">
                    <div class="detail-item"><dt class="detail-label">Empresa</dt><dd class="detail-value break-words">{{ $task->company?->trade_name ?? $task->company?->legal_name ?? '—' }}</dd></div>
                    <div class="detail-item"><dt class="detail-label">Contato</dt><dd class="detail-value break-words">{{ $task->contact?->name ?? '—' }}</dd></div>
                    <div class="detail-item"><dt class="detail-label">Lead</dt><dd class="detail-value break-words">{{ $task->lead?->name ?? $task->lead?->company_name ?? '—' }}</dd></div>
                    <div class="detail-item"><dt class="detail-label">Oportunidade</dt><dd class="detail-value break-words">{{ $task->opportunity?->title ?? '—' }}</dd></div>
                </dl>
            </x-form-section>

            @if($task->is_follow_up && ! in_array($task->status, ['completed', 'cancelled'], true))
                @can('update', $task)
                    <x-form-section title="Conclusão do follow-up" description="A conclusão criará automaticamente uma atividade no histórico.">
                        <form method="POST" action="{{ route('tasks.complete-follow-up', $task) }}">@csrf<label class="label" for="outcome">Resultado do contato</label><textarea class="input min-h-24" id="outcome" name="outcome" maxlength="2000" placeholder="Ex.: Cliente confirmou interesse e solicitou proposta."></textarea><button class="btn-primary mt-3" type="submit">Concluir follow-up</button></form>
                    </x-form-section>
                @endcan
            @endif

            @if($task->completedActivity)
                <x-form-section title="Atividade gerada" description="Este follow-up já foi concluído e registrado no histórico."><a class="font-semibold text-teal-700 hover:underline" href="{{ route('activities.show', $task->completedActivity) }}">Abrir atividade →</a></x-form-section>
            @endif
        </div>

        <aside class="space-y-4">
            <x-form-section title="Status / prioridade">
                <dl class="detail-grid xl:grid-cols-1">
                    <div class="detail-item"><dt class="detail-label">Status</dt><dd class="detail-value"><x-status-badge :variant="$statusVariant">{{ $statusLabel }}</x-status-badge></dd></div>
                    <div class="detail-item"><dt class="detail-label">Prioridade</dt><dd class="detail-value"><x-status-badge :variant="$priorityVariant">{{ $priorityLabel }}</x-status-badge></dd></div>
                </dl>
            </x-form-section>
            <x-form-section title="Prazo / responsável">
                <dl class="detail-grid xl:grid-cols-1">
                    <div class="detail-item"><dt class="detail-label">Prazo</dt><dd class="detail-value">{{ $task->due_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
                    <div class="detail-item"><dt class="detail-label">Responsável</dt><dd class="detail-value">{{ $task->assignedUser?->name ?? 'Não atribuído' }}</dd></div>
                </dl>
            </x-form-section>
            <x-form-section title="Conclusão / cancelamento">
                <dl class="detail-grid xl:grid-cols-1">
                    <div class="detail-item"><dt class="detail-label">Iniciada em</dt><dd class="detail-value">{{ $task->started_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
                    <div class="detail-item"><dt class="detail-label">Concluída em</dt><dd class="detail-value">{{ $task->completed_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
                    <div class="detail-item"><dt class="detail-label">Cancelada em</dt><dd class="detail-value">{{ $task->cancelled_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
                </dl>
            </x-form-section>
            <x-form-section title="Metadados"><dl class="detail-grid xl:grid-cols-1"><div class="detail-item"><dt class="detail-label">Criada por</dt><dd class="detail-value">{{ $task->createdByUser?->name ?? '—' }}</dd></div></dl></x-form-section>
            @can('delete', $task)
                <section class="rounded-xl border border-red-200 bg-white p-4 shadow-sm"><h2 class="font-semibold text-red-900">Excluir tarefa</h2><p class="mt-1 text-sm text-slate-600">O registro será arquivado por soft delete.</p><form class="mt-3" method="POST" action="{{ route('tasks.destroy', $task) }}" data-confirm="Deseja excluir esta tarefa?">@csrf @method('DELETE')<button class="btn-danger" type="submit">Excluir</button></form></section>
            @endcan
        </aside>
    </div>
</x-layouts.app>
