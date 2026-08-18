@php
    $advancedFilters = ['channel', 'status', 'date_from', 'date_to', 'per_page'];
    $hasAdvancedFilters = collect($advancedFilters)->contains(fn ($filter) => request()->filled($filter));
    $hasFilters = request()->except('page') !== [];
@endphp

<x-layouts.app title="Timeline Comercial">
    <x-page-header title="Timeline" description="Histórico consolidado das interações e movimentações comerciais." />

    <form class="listing-filters" method="GET" action="{{ route('timeline.index') }}">
        <x-filter-bar class="mb-0 border-0 p-0 shadow-none">
            <div class="filter-field-primary">
                <label class="label" for="q">Busca geral</label>
                <input class="input" id="q" name="q" value="{{ request('q') }}" placeholder="Atividade, empresa, contato, lead...">
            </div>
            <div class="filter-field-select">
                <label class="label" for="event_type">Tipo de evento</label>
                <select class="input" id="event_type" name="event_type">
                    <option value="">Todos</option>
                    <option value="activity" @selected(request('event_type') === 'activity')>Atividades</option>
                    <option value="follow_up" @selected(request('event_type') === 'follow_up')>Follow-ups</option>
                    <option value="task" @selected(request('event_type') === 'task')>Tarefas</option>
                </select>
            </div>
            <div class="filter-field-select">
                <label class="label" for="company_id">Empresa</label>
                <select class="input" id="company_id" name="company_id">
                    <option value="">Todas</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" @selected((string) request('company_id') === (string) $company->id)>{{ $company->trade_name ?: $company->legal_name }}</option>
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
                        <label class="label" for="channel">Canal</label>
                        <select class="input" id="channel" name="channel">
                            <option value="">Todos</option>
                            @foreach(['call' => 'Ligação', 'email' => 'E-mail', 'whatsapp' => 'WhatsApp', 'meeting' => 'Reunião', 'note' => 'Nota', 'other' => 'Outro'] as $value => $label)
                                <option value="{{ $value }}" @selected(request('channel') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label" for="status">Status</label>
                        <select class="input" id="status" name="status">
                            <option value="">Todos</option>
                            @foreach(['pending' => 'Pendente', 'in_progress' => 'Em andamento', 'completed' => 'Concluído', 'cancelled' => 'Cancelado'] as $value => $label)
                                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label class="label" for="date_from">De</label><input class="input" id="date_from" name="date_from" type="date" value="{{ request('date_from') }}"></div>
                    <div><label class="label" for="date_to">Até</label><input class="input" id="date_to" name="date_to" type="date" value="{{ request('date_to') }}"></div>
                    <div>
                        <label class="label" for="per_page">Por página</label>
                        <select class="input" id="per_page" name="per_page">
                            @foreach([15, 30, 50, 100] as $value)
                                <option value="{{ $value }}" @selected((int) request('per_page', 30) === $value)>{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </details>
            @if($hasFilters)<a class="filter-clear" href="{{ route('timeline.index') }}">Limpar filtros</a>@endif
        </div>
    </form>

    <div class="listing-summary">
        @if($timeline->total())
            Mostrando {{ $timeline->firstItem() }}–{{ $timeline->lastItem() }} de {{ $timeline->total() }} eventos no histórico comercial
        @else
            Nenhum evento encontrado no histórico comercial
        @endif
    </div>

    <div class="timeline-list" role="feed" aria-label="Histórico comercial">
        @forelse($timeline as $event)
            @php
                $eventLabel = match($event->event_type) {
                    'activity' => 'Atividade',
                    'follow_up' => 'Follow-up',
                    'task' => 'Tarefa',
                    default => 'Evento',
                };
                $eventMarker = match($event->event_type) {
                    'activity' => 'A',
                    'follow_up' => 'F',
                    'task' => 'T',
                    default => '•',
                };
                $channelLabel = match($event->channel) {
                    'call' => 'Ligação', 'email' => 'E-mail', 'whatsapp' => 'WhatsApp',
                    'meeting' => 'Reunião', 'note' => 'Nota', 'other' => 'Outro', default => null,
                };
                $statusLabel = match($event->status) {
                    'pending' => 'Pendente', 'in_progress' => 'Em andamento',
                    'completed' => 'Concluído', 'cancelled' => 'Cancelado', default => null,
                };
                $statusVariant = match($event->status) {
                    'completed' => 'success', 'pending' => 'warning',
                    'in_progress' => 'info', default => 'neutral',
                };
                $priorityLabel = match($event->priority) {
                    'low' => 'Baixa', 'medium' => 'Média', 'high' => 'Alta', 'urgent' => 'Urgente', default => null,
                };
                $eventUrl = match($event->event_type) {
                    'activity' => route('activities.show', $event->source_id),
                    'follow_up', 'task' => route('tasks.show', $event->source_id),
                    default => null,
                };
                $eventAt = $event->event_at ? \Illuminate\Support\Carbon::parse($event->event_at) : null;
            @endphp

            <article class="timeline-event" aria-label="{{ $eventLabel }}: {{ $event->title }}">
                <div class="timeline-marker" aria-hidden="true">{{ $eventMarker }}</div>
                <div class="timeline-event-body">
                    <div class="timeline-event-heading">
                        <div class="min-w-0">
                            <div class="timeline-event-badges">
                                <x-status-badge>{{ $eventLabel }}</x-status-badge>
                                @if($channelLabel)<x-status-badge variant="info">{{ $channelLabel }}</x-status-badge>@endif
                                @if($statusLabel)<x-status-badge :variant="$statusVariant">{{ $statusLabel }}</x-status-badge>@endif
                                @if($priorityLabel)<span class="timeline-priority">Prioridade {{ $priorityLabel }}</span>@endif
                            </div>
                            <h2 class="timeline-event-title">
                                @if($eventUrl)<a href="{{ $eventUrl }}">{{ $event->title }}</a>@else{{ $event->title }}@endif
                            </h2>
                        </div>
                        <time class="timeline-event-time" @if($eventAt) datetime="{{ $eventAt->toIso8601String() }}" @endif>
                            <span>{{ $eventAt?->format('d/m/Y') ?? '—' }}</span>
                            @if($eventAt)<span>{{ $eventAt->format('H:i') }}</span>@endif
                        </time>
                    </div>

                    @if($event->company_name || $event->contact_name || $event->lead_name || $event->opportunity_title)
                        <div class="timeline-entities">
                            @if($event->company_name)<span><strong>Empresa</strong> {{ $event->company_name }}</span>@endif
                            @if($event->contact_name)<span><strong>Contato</strong> {{ $event->contact_name }}</span>@endif
                            @if($event->lead_name)<span><strong>Lead</strong> {{ $event->lead_name }}</span>@endif
                            @if($event->opportunity_title)<span><strong>Oportunidade</strong> {{ $event->opportunity_title }}</span>@endif
                        </div>
                    @endif

                    @if($event->assigned_user_name)
                        <p class="timeline-assignee"><span>Responsável</span> {{ $event->assigned_user_name }}</p>
                    @endif

                    @if($event->description)<p class="timeline-summary">{{ \Illuminate\Support\Str::limit($event->description, 300) }}</p>@endif
                    @if($event->outcome)<p class="timeline-outcome"><strong>Resultado:</strong> {{ $event->outcome }}</p>@endif

                    @if($eventUrl)<a class="timeline-open-link" href="{{ $eventUrl }}" aria-label="Abrir {{ mb_strtolower($eventLabel) }}: {{ $event->title }}">Abrir <span aria-hidden="true">→</span></a>@endif
                </div>
            </article>
        @empty
            <div class="timeline-empty">
                <x-empty-state
                    :title="$hasFilters ? 'Nenhum evento encontrado.' : 'Nenhuma movimentação registrada ainda.'"
                    :description="$hasFilters ? 'Revise ou limpe os filtros para ampliar os resultados.' : 'As interações e movimentações comerciais aparecerão nesta Timeline.'"
                />
            </div>
        @endforelse
    </div>

    @if($timeline->hasPages())<div class="listing-pagination">{{ $timeline->links() }}</div>@endif
</x-layouts.app>
