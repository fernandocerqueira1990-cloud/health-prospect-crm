<x-layouts.app :title="$lead->name ?: 'Lead #'.$lead->id">
    @php
        $statusLabels = [
            'new' => 'Novo',
            'contacted' => 'Contatado',
            'qualified' => 'Qualificado',
            'nurturing' => 'Nutrição',
            'converted' => 'Convertido',
            'disqualified' => 'Desqualificado',
        ];

        $priorityLabels = [
            'low' => 'Baixa',
            'medium' => 'Média',
            'high' => 'Alta',
            'critical' => 'Crítica',
        ];

        $temperatureLabels = [
            'cold' => 'Frio',
            'warm' => 'Morno',
            'hot' => 'Quente',
        ];
    @endphp

    <x-page-header
        :title="$lead->name ?: 'Lead #'.$lead->id"
        :description="$lead->company?->trade_name
            ?: $lead->company?->legal_name
            ?: $lead->company_name
            ?: 'Potencial cliente'"
    >
        <x-slot:actions>
            <div class="flex flex-wrap gap-3">
                @can('update', $lead)
                    <a class="btn-primary" href="{{ route('leads.edit', $lead) }}">
                        Editar
                    </a>
                @endcan

                <a class="btn-secondary" href="{{ route('leads.index') }}">
                    Voltar
                </a>
            </div>
        </x-slot:actions>
    </x-page-header>

    <nav
        class="mb-6 flex gap-1 overflow-x-auto border-b border-slate-200"
        aria-label="Seções do lead"
    >
        <a
            href="#resumo"
            class="border-b-2 border-teal-700 px-4 py-3 text-sm font-semibold text-teal-800"
        >
            Resumo
        </a>

        <a
            href="#atribuicao"
            class="px-4 py-3 text-sm font-medium text-teal-700"
        >
            Origem
        </a>

        <a
            href="#historico"
            class="px-4 py-3 text-sm font-medium text-teal-700"
        >
            Histórico ({{ $events->total() }})
        </a>

        <span
            class="cursor-not-allowed px-4 py-3 text-sm text-slate-400"
            title="Disponível na Sprint 5"
        >
            Oportunidades
        </span>

        <span
            class="cursor-not-allowed px-4 py-3 text-sm text-slate-400"
            title="Disponível em sprint futura"
        >
            Atividades
        </span>
    </nav>

    <div class="grid gap-6 xl:grid-cols-3" id="resumo">
        <div class="space-y-6 xl:col-span-2">

            <section class="card">
                <h2 class="text-base font-semibold text-slate-900">
                    Dados principais
                </h2>

                <dl class="mt-4 grid gap-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Nome
                        </dt>
                        <dd class="mt-1">
                            {{ $lead->name ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Empresa informada
                        </dt>
                        <dd class="mt-1">
                            {{ $lead->company_name ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Cargo
                        </dt>
                        <dd class="mt-1">
                            {{ $lead->job_title ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Criado em
                        </dt>
                        <dd class="mt-1">
                            {{ $lead->created_at->format('d/m/Y H:i') }}
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="card">
                <h2 class="text-base font-semibold text-slate-900">
                    Contato
                </h2>

                <dl class="mt-4 grid gap-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            E-mail
                        </dt>
                        <dd class="mt-1 break-all">
                            {{ $lead->email ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Telefone
                        </dt>
                        <dd class="mt-1">
                            {{ $lead->phone ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            WhatsApp
                        </dt>
                        <dd class="mt-1">
                            {{ $lead->whatsapp ?? '—' }}
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="card">
                <h2 class="text-base font-semibold text-slate-900">
                    Vínculos no CRM
                </h2>

                <dl class="mt-4 grid gap-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Empresa
                        </dt>

                        <dd class="mt-1">
                            @if($lead->company)
                                @can('view', $lead->company)
                                    <a
                                        class="font-semibold text-teal-700 hover:text-teal-900"
                                        href="{{ route('companies.show', $lead->company) }}"
                                    >
                                        {{ $lead->company->trade_name ?: $lead->company->legal_name }}
                                    </a>
                                @else
                                    {{ $lead->company->trade_name ?: $lead->company->legal_name }}
                                @endcan
                            @else
                                —
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Contato
                        </dt>

                        <dd class="mt-1">
                            @if($lead->contact)
                                @can('view', $lead->contact)
                                    <a
                                        class="font-semibold text-teal-700 hover:text-teal-900"
                                        href="{{ route('contacts.show', $lead->contact) }}"
                                    >
                                        {{ $lead->contact->name }}
                                    </a>
                                @else
                                    {{ $lead->contact->name }}
                                @endcan
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="card" id="atribuicao">
                <h2 class="text-base font-semibold text-slate-900">
                    Aquisição e atribuição
                </h2>

                <dl class="mt-4 grid gap-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Origem
                        </dt>
                        <dd class="mt-1">
                            {{ $lead->source?->name ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Canal
                        </dt>
                        <dd class="mt-1">
                            {{ $lead->channel?->name ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            First Touch
                        </dt>

                        <dd class="mt-1">
                            @if($lead->firstTouchSourceEvent)
                                {{ $lead->firstTouchSourceEvent->channel ?: $lead->firstTouchSourceEvent->source ?: 'Evento #'.$lead->firstTouchSourceEvent->id }}

                                <span class="text-xs text-slate-500">
                                    · {{ $lead->firstTouchSourceEvent->occurred_at->format('d/m/Y H:i') }}
                                </span>
                            @else
                                —
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Last Touch
                        </dt>

                        <dd class="mt-1">
                            @if($lead->lastTouchSourceEvent)
                                {{ $lead->lastTouchSourceEvent->channel ?: $lead->lastTouchSourceEvent->source ?: 'Evento #'.$lead->lastTouchSourceEvent->id }}

                                <span class="text-xs text-slate-500">
                                    · {{ $lead->lastTouchSourceEvent->occurred_at->format('d/m/Y H:i') }}
                                </span>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="card">
                <h2 class="text-base font-semibold text-slate-900">
                    Acompanhamento
                </h2>

                <dl class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Última interação
                        </dt>
                        <dd class="mt-1">
                            {{ $lead->last_interaction_at?->format('d/m/Y H:i') ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Próxima ação
                        </dt>
                        <dd class="mt-1">
                            {{ $lead->next_action_at?->format('d/m/Y H:i') ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Qualificado em
                        </dt>
                        <dd class="mt-1">
                            {{ $lead->qualified_at?->format('d/m/Y H:i') ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Convertido em
                        </dt>
                        <dd class="mt-1">
                            {{ $lead->converted_at?->format('d/m/Y H:i') ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Perdido / desqualificado
                        </dt>
                        <dd class="mt-1">
                            {{ $lead->lost_at?->format('d/m/Y H:i') ?? '—' }}
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="card">
                <h2 class="text-base font-semibold text-slate-900">
                    Observações
                </h2>

                <p class="mt-4 whitespace-pre-line text-sm text-slate-700">
                    {{ $lead->notes ?? 'Nenhuma observação registrada.' }}
                </p>
            </section>

            <section class="card" id="historico">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">
                            Histórico de origem
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Eventos utilizados para rastrear aquisição e atribuição.
                        </p>
                    </div>

                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                        {{ $events->total() }} eventos
                    </span>
                </div>

                <div class="mt-5 overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Evento</th>
                                <th>Origem</th>
                                <th>Canal / meio</th>
                                <th>Campanha</th>
                                <th>UTM</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($events as $event)
                                <tr>
                                    <td>
                                        {{ $event->occurred_at->format('d/m/Y H:i') }}
                                    </td>

                                    <td>
                                        {{ $event->event_type }}
                                    </td>

                                    <td>
                                        {{ $event->source ?? '—' }}
                                    </td>

                                    <td>
                                        {{ collect([$event->channel, $event->medium])->filter()->join(' · ') ?: '—' }}
                                    </td>

                                    <td>
                                        {{ $event->campaign ?? $event->utm_campaign ?? '—' }}
                                    </td>

                                    <td>
                                        {{ collect([
                                            $event->utm_source,
                                            $event->utm_medium,
                                            $event->utm_content,
                                            $event->utm_term,
                                        ])->filter()->join(' · ') ?: '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">
                                        Nenhum evento de origem registrado.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-5">
                    {{ $events->links() }}
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="card">
                <h2 class="text-base font-semibold text-slate-900">
                    Comercial
                </h2>

                <dl class="mt-4 space-y-4">
                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Status
                        </dt>
                        <dd class="mt-1">
                            {{ $statusLabels[$lead->status] ?? $lead->status }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Responsável
                        </dt>
                        <dd class="mt-1">
                            {{ $lead->assignedUser?->name ?? 'Não atribuído' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Prioridade
                        </dt>
                        <dd class="mt-1">
                            {{ $priorityLabels[$lead->priority] ?? 'Não definida' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Temperatura
                        </dt>
                        <dd class="mt-1">
                            {{ $temperatureLabels[$lead->temperature] ?? 'Não definida' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Score
                        </dt>
                        <dd class="mt-1 text-xl font-bold text-slate-950">
                            {{ $lead->score }}
                            <span class="text-sm font-normal text-slate-400">/100</span>
                        </dd>
                    </div>
                </dl>
            </section>

            @can('delete', $lead)
                <section class="rounded-xl border border-red-200 bg-white p-5">
                    <h2 class="font-semibold text-red-900">
                        Excluir lead
                    </h2>

                    <p class="mt-2 text-sm text-slate-600">
                        O lead será arquivado por soft delete e não será removido definitivamente.
                    </p>

                    <form
                        class="mt-4"
                        method="POST"
                        action="{{ route('leads.destroy', $lead) }}"
                        onsubmit="return confirm('Confirma a exclusão deste lead?')"
                    >
                        @csrf
                        @method('DELETE')

                        <button
                            class="inline-flex rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-800"
                            type="submit"
                        >
                            Excluir lead
                        </button>
                    </form>
                </section>
            @endcan
        </aside>
    </div>
</x-layouts.app>
