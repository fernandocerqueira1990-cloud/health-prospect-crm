<x-layouts.app title="{{ $activity->subject }}">
    @php
        $typeLabel = match($activity->type) { 'call' => 'Ligação', 'email' => 'E-mail', 'whatsapp' => 'WhatsApp', 'meeting' => 'Reunião', 'note' => 'Nota', default => 'Outro' };
        $directionLabel = match($activity->direction) { 'outbound' => 'Saída', 'inbound' => 'Entrada', default => '—' };
    @endphp

    <x-page-header :title="$activity->subject" :description="$typeLabel">
        <x-slot:actions>
            @can('update', $activity)<a class="btn-primary" href="{{ route('activities.edit', $activity) }}">Editar</a>@endcan
            <a class="btn-secondary" href="{{ route('activities.index') }}">Voltar</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 xl:grid-cols-3">
        <div class="space-y-4 xl:col-span-2">
            <x-form-section title="Identificação">
                <dl class="detail-grid lg:grid-cols-4">
                    <div class="detail-item lg:col-span-2"><dt class="detail-label">Assunto</dt><dd class="detail-value break-words">{{ $activity->subject }}</dd></div>
                    <div class="detail-item"><dt class="detail-label">Tipo</dt><dd class="detail-value"><x-status-badge variant="info">{{ $typeLabel }}</x-status-badge></dd></div>
                    <div class="detail-item"><dt class="detail-label">Direção</dt><dd class="detail-value">{{ $directionLabel }}</dd></div>
                </dl>
            </x-form-section>

            <x-form-section title="Vínculos comerciais">
                <dl class="detail-grid lg:grid-cols-4">
                    <div class="detail-item"><dt class="detail-label">Empresa</dt><dd class="detail-value break-words">@if($activity->company)<a class="text-teal-700 hover:underline" href="{{ route('companies.show', $activity->company) }}">{{ $activity->company->trade_name ?: $activity->company->legal_name }}</a>@else — @endif</dd></div>
                    <div class="detail-item"><dt class="detail-label">Contato</dt><dd class="detail-value break-words">@if($activity->contact)<a class="text-teal-700 hover:underline" href="{{ route('contacts.show', $activity->contact) }}">{{ $activity->contact->name }}</a>@else — @endif</dd></div>
                    <div class="detail-item"><dt class="detail-label">Lead</dt><dd class="detail-value break-words">@if($activity->lead)<a class="text-teal-700 hover:underline" href="{{ route('leads.show', $activity->lead) }}">{{ $activity->lead->name ?: $activity->lead->company_name ?: 'Lead #'.$activity->lead->id }}</a>@else — @endif</dd></div>
                    <div class="detail-item"><dt class="detail-label">Oportunidade</dt><dd class="detail-value break-words">@if($activity->opportunity)<a class="text-teal-700 hover:underline" href="{{ route('opportunities.show', $activity->opportunity) }}">{{ $activity->opportunity->title }}</a>@else — @endif</dd></div>
                </dl>
            </x-form-section>

            <x-form-section title="Resultado / observações">
                <div class="grid gap-4 md:grid-cols-2">
                    <div><p class="detail-label">Descrição</p><p class="mt-1 whitespace-pre-line break-words text-sm leading-6 text-slate-700">{{ $activity->description ?? 'Nenhuma descrição registrada.' }}</p></div>
                    <div><p class="detail-label">Resultado</p><p class="mt-1 whitespace-pre-line break-words text-sm leading-6 text-slate-700">{{ $activity->outcome ?? 'Nenhum resultado registrado.' }}</p></div>
                </div>
            </x-form-section>
        </div>

        <aside class="space-y-4">
            <x-form-section title="Agendamento">
                <dl class="detail-grid xl:grid-cols-1">
                    <div class="detail-item"><dt class="detail-label">Data e hora</dt><dd class="detail-value">{{ $activity->occurred_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
                    <div class="detail-item"><dt class="detail-label">Duração</dt><dd class="detail-value">{{ $activity->duration_minutes ? $activity->duration_minutes.' minutos' : '—' }}</dd></div>
                </dl>
            </x-form-section>
            <x-form-section title="Responsável">
                <dl class="detail-grid xl:grid-cols-1"><div class="detail-item"><dt class="detail-label">Responsável</dt><dd class="detail-value">{{ $activity->assignedUser?->name ?? 'Não atribuído' }}</dd></div></dl>
            </x-form-section>
            <x-form-section title="Metadados">
                <dl class="detail-grid xl:grid-cols-1">
                    <div class="detail-item"><dt class="detail-label">Registrado por</dt><dd class="detail-value">{{ $activity->createdByUser?->name ?? '—' }}</dd></div>
                    <div class="detail-item"><dt class="detail-label">Criado em</dt><dd class="detail-value">{{ $activity->created_at?->format('d/m/Y H:i') }}</dd></div>
                </dl>
            </x-form-section>
            @can('delete', $activity)
                <section class="rounded-xl border border-red-200 bg-white p-4 shadow-sm"><h2 class="font-semibold text-red-900">Excluir atividade</h2><p class="mt-1 text-sm text-slate-600">A atividade será arquivada por soft delete.</p><form class="mt-3" method="POST" action="{{ route('activities.destroy', $activity) }}" data-confirm="Deseja excluir esta atividade?">@csrf @method('DELETE')<button class="btn-danger" type="submit">Excluir</button></form></section>
            @endcan
        </aside>
    </div>
</x-layouts.app>
