<x-layouts.app title="Timeline Comercial">
    <x-page-header
        title="Timeline Comercial"
        description="Histórico cronológico de atividades, follow-ups e tarefas comerciais."
    />

    <form
        class="card mb-6"
        method="GET"
        action="{{ route('timeline.index') }}"
    >
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="sm:col-span-2">
                <label class="label" for="q">Busca geral</label>

                <input
                    class="input"
                    id="q"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Atividade, empresa, contato, lead..."
                >
            </div>

            <div>
                <label class="label" for="event_type">Tipo de evento</label>

                <select class="input" id="event_type" name="event_type">
                    <option value="">Todos</option>

                    <option
                        value="activity"
                        @selected(request('event_type') === 'activity')
                    >
                        Atividades
                    </option>

                    <option
                        value="follow_up"
                        @selected(request('event_type') === 'follow_up')
                    >
                        Follow-ups
                    </option>

                    <option
                        value="task"
                        @selected(request('event_type') === 'task')
                    >
                        Tarefas
                    </option>
                </select>
            </div>

            <div>
                <label class="label" for="channel">Canal</label>

                <select class="input" id="channel" name="channel">
                    <option value="">Todos</option>

                    @foreach([
                        'call' => 'Ligação',
                        'email' => 'E-mail',
                        'whatsapp' => 'WhatsApp',
                        'meeting' => 'Reunião',
                        'note' => 'Nota',
                        'other' => 'Outro',
                    ] as $value => $label)
                        <option
                            value="{{ $value }}"
                            @selected(request('channel') === $value)
                        >
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="label" for="status">Status</label>

                <select class="input" id="status" name="status">
                    <option value="">Todos</option>

                    @foreach([
                        'pending' => 'Pendente',
                        'in_progress' => 'Em andamento',
                        'completed' => 'Concluído',
                        'cancelled' => 'Cancelado',
                    ] as $value => $label)
                        <option
                            value="{{ $value }}"
                            @selected(request('status') === $value)
                        >
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="label" for="company_id">Empresa</label>

                <select class="input" id="company_id" name="company_id">
                    <option value="">Todas</option>

                    @foreach($companies as $company)
                        <option
                            value="{{ $company->id }}"
                            @selected(
                                (string) request('company_id')
                                === (string) $company->id
                            )
                        >
                            {{ $company->trade_name ?: $company->legal_name }}
                        </option>
                    @endforeach
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
                <label class="label" for="date_from">De</label>

                <input
                    class="input"
                    id="date_from"
                    name="date_from"
                    type="date"
                    value="{{ request('date_from') }}"
                >
            </div>

            <div>
                <label class="label" for="date_to">Até</label>

                <input
                    class="input"
                    id="date_to"
                    name="date_to"
                    type="date"
                    value="{{ request('date_to') }}"
                >
            </div>

            <div>
                <label class="label" for="per_page">Por página</label>

                <select class="input" id="per_page" name="per_page">
                    @foreach([15, 30, 50, 100] as $value)
                        <option
                            value="{{ $value }}"
                            @selected(
                                (int) request('per_page', 30) === $value
                            )
                        >
                            {{ $value }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-5 flex flex-wrap gap-3">
            <button class="btn-primary" type="submit">
                Filtrar
            </button>

            <a class="btn-secondary" href="{{ route('timeline.index') }}">
                Limpar
            </a>
        </div>
    </form>

    <div class="mb-5">
        <h2 class="text-xl font-bold text-slate-950">
            Histórico comercial
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            {{ $timeline->total() }}
            {{ $timeline->total() === 1 ? 'evento encontrado' : 'eventos encontrados' }}
        </p>
    </div>

    <div class="relative space-y-4">
        @forelse($timeline as $event)
            @php
                $eventLabel = match($event->event_type) {
                    'activity' => 'Atividade',
                    'follow_up' => 'Follow-up',
                    'task' => 'Tarefa',
                    default => 'Evento',
                };

                $channelLabel = match($event->channel) {
                    'call' => 'Ligação',
                    'email' => 'E-mail',
                    'whatsapp' => 'WhatsApp',
                    'meeting' => 'Reunião',
                    'note' => 'Nota',
                    'other' => 'Outro',
                    default => null,
                };

                $statusLabel = match($event->status) {
                    'pending' => 'Pendente',
                    'in_progress' => 'Em andamento',
                    'completed' => 'Concluído',
                    'cancelled' => 'Cancelado',
                    default => null,
                };

                $priorityLabel = match($event->priority) {
                    'low' => 'Baixa',
                    'medium' => 'Média',
                    'high' => 'Alta',
                    'urgent' => 'Urgente',
                    default => null,
                };

                $eventUrl = match($event->event_type) {
                    'activity' => route(
                        'activities.show',
                        $event->source_id
                    ),
                    'follow_up',
                    'task' => route(
                        'tasks.show',
                        $event->source_id
                    ),
                    default => null,
                };

                $eventAt = $event->event_at
                    ? \Illuminate\Support\Carbon::parse(
                        $event->event_at
                    )
                    : null;
            @endphp

            <article class="card">
                <div
                    class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700"
                            >
                                {{ $eventLabel }}
                            </span>

                            @if($channelLabel)
                                <span
                                    class="rounded-full bg-teal-50 px-3 py-1 text-xs font-semibold text-teal-700"
                                >
                                    {{ $channelLabel }}
                                </span>
                            @endif

                            @if($statusLabel)
                                <span
                                    class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600"
                                >
                                    {{ $statusLabel }}
                                </span>
                            @endif

                            @if($priorityLabel)
                                <span
                                    class="text-xs font-semibold text-slate-500"
                                >
                                    Prioridade {{ $priorityLabel }}
                                </span>
                            @endif
                        </div>

                        <h3 class="mt-3 text-lg font-bold text-slate-950">
                            {{ $event->title }}
                        </h3>

                        @if($event->description)
                            <p class="mt-2 text-sm text-slate-600">
                                {{ \Illuminate\Support\Str::limit(
                                    $event->description,
                                    300
                                ) }}
                            </p>
                        @endif

                        @if($event->outcome)
                            <div
                                class="mt-3 rounded-lg bg-slate-50 p-3 text-sm text-slate-700"
                            >
                                <span class="font-semibold">
                                    Resultado:
                                </span>

                                {{ $event->outcome }}
                            </div>
                        @endif

                        <div
                            class="mt-4 grid gap-x-6 gap-y-2 text-sm text-slate-500 sm:grid-cols-2"
                        >
                            @if($event->company_name)
                                <span>
                                    Empresa:
                                    <strong class="text-slate-700">
                                        {{ $event->company_name }}
                                    </strong>
                                </span>
                            @endif

                            @if($event->contact_name)
                                <span>
                                    Contato:
                                    <strong class="text-slate-700">
                                        {{ $event->contact_name }}
                                    </strong>
                                </span>
                            @endif

                            @if($event->lead_name)
                                <span>
                                    Lead:
                                    <strong class="text-slate-700">
                                        {{ $event->lead_name }}
                                    </strong>
                                </span>
                            @endif

                            @if($event->opportunity_title)
                                <span>
                                    Oportunidade:
                                    <strong class="text-slate-700">
                                        {{ $event->opportunity_title }}
                                    </strong>
                                </span>
                            @endif
                        </div>

                        @if($event->assigned_user_name)
                            <p class="mt-3 text-sm text-slate-500">
                                Responsável:
                                <strong class="text-slate-700">
                                    {{ $event->assigned_user_name }}
                                </strong>
                            </p>
                        @endif
                    </div>

                    <div class="shrink-0 sm:text-right">
                        <p class="font-semibold text-slate-900">
                            {{ $eventAt?->format('d/m/Y') ?? '—' }}
                        </p>

                        <p class="text-sm text-slate-500">
                            {{ $eventAt?->format('H:i') ?? '' }}
                        </p>

                        @if($eventUrl)
                            <a
                                class="mt-4 inline-block font-semibold text-teal-700 hover:text-teal-900"
                                href="{{ $eventUrl }}"
                            >
                                Abrir →
                            </a>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <div class="card text-center">
                <h3 class="font-semibold text-slate-900">
                    Nenhum evento encontrado
                </h3>

                <p class="mt-2 text-sm text-slate-500">
                    Ajuste os filtros ou registre novas interações comerciais.
                </p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $timeline->links() }}
    </div>
</x-layouts.app>
