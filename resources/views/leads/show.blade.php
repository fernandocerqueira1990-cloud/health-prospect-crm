<x-layouts.app :title="$lead->name ?: 'Lead #'.$lead->id">
    @php
        $statusLabels = ['new' => 'Novo', 'contacted' => 'Contatado', 'qualified' => 'Qualificado', 'nurturing' => 'Nutrição', 'converted' => 'Convertido', 'disqualified' => 'Desqualificado'];
        $priorityLabels = ['low' => 'Baixa', 'medium' => 'Média', 'high' => 'Alta', 'critical' => 'Crítica'];
        $temperatureLabels = ['cold' => 'Frio', 'warm' => 'Morno', 'hot' => 'Quente'];
        $statusVariant = match($lead->status) { 'converted' => 'success', 'qualified' => 'info', 'disqualified' => 'danger', 'contacted', 'nurturing' => 'warning', default => 'neutral' };
        $priorityVariant = match($lead->priority) { 'critical' => 'danger', 'high' => 'warning', default => 'neutral' };
        $temperatureVariant = match($lead->temperature) { 'hot' => 'danger', 'warm' => 'warning', 'cold' => 'info', default => 'neutral' };
    @endphp

    <x-page-header
        :title="$lead->name ?: 'Lead #'.$lead->id"
        :description="$lead->company?->trade_name ?: $lead->company?->legal_name ?: $lead->company_name ?: 'Detalhes da oportunidade comercial.'"
    >
        <x-slot:actions>
            <div class="flex flex-wrap gap-2">
                @can('update', $lead)<a class="btn-primary" href="{{ route('leads.edit', $lead) }}">Editar</a>@endcan
                <a class="btn-secondary" href="{{ route('leads.index') }}">Voltar</a>
            </div>
        </x-slot:actions>
    </x-page-header>

    <nav class="mb-4 flex gap-1 overflow-x-auto border-b border-slate-200" aria-label="Seções do lead">
        <a href="#resumo" class="border-b-2 border-teal-700 px-3 py-2.5 text-sm font-semibold text-teal-800">Resumo</a>
        <a href="#aquisicao" class="px-3 py-2.5 text-sm font-medium text-teal-700">Origem</a>
        <a href="#historico" class="px-3 py-2.5 text-sm font-medium text-teal-700">Histórico ({{ $events->total() }})</a>
        <span class="cursor-not-allowed px-3 py-2.5 text-sm text-slate-400" title="Disponível na Sprint 5">Oportunidades</span>
        <span class="cursor-not-allowed px-3 py-2.5 text-sm text-slate-400" title="Disponível em sprint futura">Atividades</span>
    </nav>

    <div class="grid gap-4 xl:grid-cols-3" id="resumo">
        <div class="space-y-4 xl:col-span-2">
            <x-form-section title="Identificação">
                <dl class="detail-grid lg:grid-cols-4">
                    <div class="detail-item lg:col-span-2"><dt class="detail-label">Nome</dt><dd class="detail-value">{{ $lead->name ?? '—' }}</dd></div>
                    <div class="detail-item"><dt class="detail-label">Empresa informada</dt><dd class="detail-value">{{ $lead->company_name ?? '—' }}</dd></div>
                    <div class="detail-item"><dt class="detail-label">Cargo</dt><dd class="detail-value">{{ $lead->job_title ?? '—' }}</dd></div>
                    <div class="detail-item lg:col-span-2"><dt class="detail-label">Empresa no CRM</dt><dd class="detail-value">@if($lead->company)@can('view', $lead->company)<a class="text-teal-700 hover:underline" href="{{ route('companies.show', $lead->company) }}">{{ $lead->company->trade_name ?: $lead->company->legal_name }}</a>@else{{ $lead->company->trade_name ?: $lead->company->legal_name }}@endcan @else — @endif</dd></div>
                    <div class="detail-item lg:col-span-2"><dt class="detail-label">Contato no CRM</dt><dd class="detail-value">@if($lead->contact)@can('view', $lead->contact)<a class="text-teal-700 hover:underline" href="{{ route('contacts.show', $lead->contact) }}">{{ $lead->contact->name }}</a>@else{{ $lead->contact->name }}@endcan @else — @endif</dd></div>
                </dl>
            </x-form-section>

            <x-form-section title="Contato">
                <dl class="detail-grid lg:grid-cols-3">
                    <div class="detail-item"><dt class="detail-label">E-mail</dt><dd class="detail-value break-all">@if($lead->email)<a class="text-teal-700 hover:underline" href="mailto:{{ $lead->email }}">{{ $lead->email }}</a>@else — @endif</dd></div>
                    <div class="detail-item"><dt class="detail-label">Telefone</dt><dd class="detail-value">@if($lead->phone)<a class="text-teal-700 hover:underline" href="tel:{{ $lead->phone }}">{{ $lead->phone }}</a>@else — @endif</dd></div>
                    <div class="detail-item"><dt class="detail-label">WhatsApp</dt><dd class="detail-value">@if($lead->whatsapp)<a class="text-teal-700 hover:underline" href="https://wa.me/{{ preg_replace('/\D/', '', $lead->whatsapp) }}" target="_blank" rel="noopener noreferrer">{{ $lead->whatsapp }}</a>@else — @endif</dd></div>
                </dl>
            </x-form-section>

            <x-form-section id="aquisicao" title="Aquisição" description="Origem, canal e pontos de contato registrados.">
                <dl class="detail-grid lg:grid-cols-4">
                    <div class="detail-item"><dt class="detail-label">Origem</dt><dd class="detail-value">{{ $lead->source?->name ?? '—' }}</dd></div>
                    <div class="detail-item"><dt class="detail-label">Canal</dt><dd class="detail-value">{{ $lead->channel?->name ?? '—' }}</dd></div>
                    <div class="detail-item"><dt class="detail-label">First Touch</dt><dd class="detail-value">@if($lead->firstTouchSourceEvent){{ $lead->firstTouchSourceEvent->channel ?: $lead->firstTouchSourceEvent->source ?: 'Evento #'.$lead->firstTouchSourceEvent->id }} <span class="block text-xs font-normal text-slate-500">{{ $lead->firstTouchSourceEvent->occurred_at->format('d/m/Y H:i') }}</span>@else — @endif</dd></div>
                    <div class="detail-item"><dt class="detail-label">Last Touch</dt><dd class="detail-value">@if($lead->lastTouchSourceEvent){{ $lead->lastTouchSourceEvent->channel ?: $lead->lastTouchSourceEvent->source ?: 'Evento #'.$lead->lastTouchSourceEvent->id }} <span class="block text-xs font-normal text-slate-500">{{ $lead->lastTouchSourceEvent->occurred_at->format('d/m/Y H:i') }}</span>@else — @endif</dd></div>
                </dl>
            </x-form-section>

            <x-form-section title="Acompanhamento">
                <dl class="detail-grid lg:grid-cols-3">
                    <div class="detail-item"><dt class="detail-label">Última interação</dt><dd class="detail-value">{{ $lead->last_interaction_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
                    <div class="detail-item"><dt class="detail-label">Próxima ação</dt><dd class="detail-value">{{ $lead->next_action_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
                    <div class="detail-item"><dt class="detail-label">Qualificado em</dt><dd class="detail-value">{{ $lead->qualified_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
                    <div class="detail-item"><dt class="detail-label">Convertido em</dt><dd class="detail-value">{{ $lead->converted_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
                    <div class="detail-item"><dt class="detail-label">Perdido / desqualificado</dt><dd class="detail-value">{{ $lead->lost_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
                </dl>
            </x-form-section>

            <x-form-section title="Observações">
                <p class="whitespace-pre-line break-words text-sm leading-6 text-slate-700">{{ $lead->notes ?? 'Nenhuma observação registrada.' }}</p>
            </x-form-section>

            <x-form-section id="historico" title="Histórico de origem" description="Eventos utilizados para rastrear aquisição e atribuição.">
                <x-slot:actions><x-status-badge>{{ $events->total() }} eventos</x-status-badge></x-slot:actions>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>Data</th><th>Evento</th><th>Origem</th><th>Canal / meio</th><th>Campanha</th><th>UTM</th></tr></thead>
                        <tbody>
                            @forelse($events as $event)
                                <tr>
                                    <td>{{ $event->occurred_at->format('d/m/Y H:i') }}</td><td>{{ $event->event_type }}</td><td>{{ $event->source ?? '—' }}</td><td>{{ collect([$event->channel, $event->medium])->filter()->join(' · ') ?: '—' }}</td><td>{{ $event->campaign ?? $event->utm_campaign ?? '—' }}</td><td>{{ collect([$event->utm_source, $event->utm_medium, $event->utm_content, $event->utm_term])->filter()->join(' · ') ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center">Nenhum evento de origem registrado.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($events->hasPages())<div class="mt-4">{{ $events->links() }}</div>@endif
            </x-form-section>
        </div>

        <aside class="space-y-4">
            <x-form-section title="Qualificação comercial">
                <dl class="detail-grid xl:grid-cols-1">
                    <div class="detail-item"><dt class="detail-label">Status</dt><dd class="detail-value"><x-status-badge :variant="$statusVariant">{{ $statusLabels[$lead->status] ?? $lead->status }}</x-status-badge></dd></div>
                    <div class="detail-item"><dt class="detail-label">Temperatura</dt><dd class="detail-value"><x-status-badge :variant="$temperatureVariant">{{ $temperatureLabels[$lead->temperature] ?? 'Não definida' }}</x-status-badge></dd></div>
                    <div class="detail-item"><dt class="detail-label">Prioridade</dt><dd class="detail-value"><x-status-badge :variant="$priorityVariant">{{ $priorityLabels[$lead->priority] ?? 'Não definida' }}</x-status-badge></dd></div>
                    <div class="detail-item"><dt class="detail-label">Responsável</dt><dd class="detail-value">{{ $lead->assignedUser?->name ?? 'Não atribuído' }}</dd></div>
                    <div class="rounded-lg border border-teal-100 bg-teal-50/60 p-3"><dt class="detail-label text-teal-700">Score</dt><dd class="mt-1 text-3xl font-bold tracking-tight text-slate-950">{{ $lead->score }}<span class="ml-1 text-sm font-medium text-slate-400">/100</span></dd></div>
                </dl>
            </x-form-section>

            <x-form-section title="Metadados">
                <dl class="detail-grid xl:grid-cols-1">
                    <div class="detail-item"><dt class="detail-label">Criado em</dt><dd class="detail-value">{{ $lead->created_at->format('d/m/Y H:i') }}</dd></div>
                    <div class="detail-item"><dt class="detail-label">Atualizado em</dt><dd class="detail-value">{{ $lead->updated_at->format('d/m/Y H:i') }}</dd></div>
                </dl>
            </x-form-section>

            @can('delete', $lead)
                <section class="rounded-xl border border-red-200 bg-white p-4 shadow-sm">
                    <h2 class="font-semibold text-red-900">Excluir lead</h2>
                    <p class="mt-1 text-sm text-slate-600">O lead será arquivado por soft delete e não será removido definitivamente.</p>
                    <form class="mt-3" method="POST" action="{{ route('leads.destroy', $lead) }}" data-confirm="Confirma a exclusão deste lead?">
                        @csrf @method('DELETE')
                        <button class="btn-danger" type="submit">Excluir lead</button>
                    </form>
                </section>
            @endcan
        </aside>
    </div>
</x-layouts.app>
