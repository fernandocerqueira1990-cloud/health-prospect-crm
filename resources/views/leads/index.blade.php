@php
    $statusLabels = [
        'new' => 'Novo',
        'contacted' => 'Contatado',
        'qualified' => 'Qualificado',
        'nurturing' => 'Nutrição',
        'converted' => 'Convertido',
        'disqualified' => 'Desqualificado',
    ];
    $statusVariants = [
        'new' => 'info',
        'contacted' => 'info',
        'qualified' => 'success',
        'nurturing' => 'info',
        'converted' => 'success',
        'disqualified' => 'neutral',
    ];
    $temperatureLabels = ['cold' => 'Frio', 'warm' => 'Morno', 'hot' => 'Quente'];
    $temperatureVariants = ['cold' => 'neutral', 'warm' => 'warning', 'hot' => 'danger'];
    $priorityLabels = ['low' => 'Baixa', 'medium' => 'Média', 'high' => 'Alta', 'critical' => 'Crítica'];
    $advancedFilters = ['source_id', 'channel_id', 'priority', 'temperature', 'inactive', 'per_page'];
    $hasAdvancedFilters = collect($advancedFilters)->contains(fn ($filter) => request()->filled($filter));
    $hasFilters = request()->except('page') !== [];
    $inactivityDays = max(1, (int) config('commercial.lead_inactivity_days', 7));
    $inactivityCutoff = now()->subDays($inactivityDays);
@endphp

<x-layouts.app title="Leads">
    <x-page-header title="Leads" description="Captação, qualificação, origem e acompanhamento comercial.">
        <x-slot:actions>
            @can('create', App\Models\Lead::class)
                <a class="btn-primary" href="{{ route('leads.create') }}">Novo lead</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <form class="listing-filters" method="GET" action="{{ route('leads.index') }}">
        <x-filter-bar class="mb-0 border-0 p-0 shadow-none">
            <div class="filter-field-primary">
                <label class="label" for="q">Busca geral</label>
                <input class="input" id="q" name="q" value="{{ request('q') }}" placeholder="Nome, empresa, cargo, e-mail, telefone ou WhatsApp">
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

            <x-slot:actions>
                <button class="btn-primary" type="submit">Filtrar</button>
            </x-slot:actions>
        </x-filter-bar>

        <div class="listing-filter-footer">
            <details class="advanced-filters" @if($hasAdvancedFilters) open @endif>
                <summary>Filtros avançados</summary>
                <div class="advanced-filter-grid">
                    <div>
                        <label class="label" for="source_id">Origem</label>
                        <select class="input" id="source_id" name="source_id">
                            <option value="">Todas</option>
                            @foreach($sources as $source)
                                <option value="{{ $source->id }}" @selected((string) request('source_id') === (string) $source->id)>{{ $source->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label" for="channel_id">Canal</label>
                        <select class="input" id="channel_id" name="channel_id">
                            <option value="">Todos</option>
                            @foreach($channels as $channel)
                                <option value="{{ $channel->id }}" @selected((string) request('channel_id') === (string) $channel->id)>{{ $channel->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label" for="priority">Prioridade</label>
                        <select class="input" id="priority" name="priority">
                            <option value="">Todas</option>
                            @foreach($priorityLabels as $value => $label)
                                <option value="{{ $value }}" @selected(request('priority') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label" for="temperature">Temperatura</label>
                        <select class="input" id="temperature" name="temperature">
                            <option value="">Todas</option>
                            @foreach($temperatureLabels as $value => $label)
                                <option value="{{ $value }}" @selected(request('temperature') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label" for="inactive">Interação</label>
                        <select class="input" id="inactive" name="inactive">
                            <option value="">Todos</option>
                            <option value="1" @selected(request('inactive') === '1')>Sem interação há {{ $inactivityDays }} dias</option>
                        </select>
                    </div>
                    <div>
                        <label class="label" for="per_page">Por página</label>
                        <select class="input" id="per_page" name="per_page">
                            @foreach([10, 15, 25, 50, 100] as $value)
                                <option value="{{ $value }}" @selected((int) request('per_page', 15) === $value)>{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </details>

            @if($hasFilters)
                <a class="filter-clear" href="{{ route('leads.index') }}">Limpar filtros</a>
            @endif
        </div>
    </form>

    <div class="listing-summary">
        @if($leads->total())
            Mostrando {{ $leads->firstItem() }}–{{ $leads->lastItem() }} de {{ $leads->total() }} leads
        @else
            Nenhum lead encontrado
        @endif
    </div>

    <div class="table-wrap">
        <table class="table listing-table">
            <thead>
                <tr>
                    <th>Lead</th>
                    <th>Origem / Canal</th>
                    <th>Status</th>
                    <th>Temperatura</th>
                    <th>Score</th>
                    <th>Responsável</th>
                    <th>Próxima ação</th>
                    <th class="text-right">AÇÕES</th>
                </tr>
            </thead>
            <tbody>
                @forelse($leads as $lead)
                    @php
                        $isInactive = ! in_array($lead->status, ['converted', 'disqualified'], true)
                            && $lead->created_at->lte($inactivityCutoff)
                            && ($lead->last_interaction_at === null || $lead->last_interaction_at->lte($inactivityCutoff));
                    @endphp
                    <tr>
                        <td>
                            <a class="table-primary-link" href="{{ route('leads.show', $lead) }}">{{ $lead->name ?: 'Lead #'.$lead->id }}</a>
                            <span class="table-secondary-line">
                                @if($lead->company)
                                    {{ $lead->company->trade_name ?: $lead->company->legal_name }}
                                @elseif($lead->company_name)
                                    {{ $lead->company_name }}
                                @else
                                    Empresa não informada
                                @endif
                            </span>
                            @if($lead->job_title)
                                <span class="table-secondary-line text-slate-400">{{ $lead->job_title }}</span>
                            @endif
                            @if($isInactive)
                                <span class="mt-1 inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-bold text-amber-700">Sem interação há {{ $inactivityDays }}+ dias</span>
                            @endif
                        </td>
                        <td>
                            <span class="font-medium text-slate-800">{{ $lead->source?->name ?? '—' }}</span>
                            <span class="table-secondary-line">{{ $lead->channel?->name ?? '—' }}</span>
                        </td>
                        <td><x-status-badge :variant="$statusVariants[$lead->status] ?? 'neutral'">{{ $statusLabels[$lead->status] ?? $lead->status }}</x-status-badge></td>
                        <td>
                            @if($lead->temperature)
                                <x-status-badge :variant="$temperatureVariants[$lead->temperature] ?? 'neutral'">{{ $temperatureLabels[$lead->temperature] ?? $lead->temperature }}</x-status-badge>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td><span class="font-semibold text-slate-950">{{ $lead->score }}</span> <span class="text-xs text-slate-400">/100</span></td>
                        <td>
                            @if($lead->assignedUser)
                                {{ $lead->assignedUser->name }}
                            @else
                                <span class="text-slate-500">Não atribuído</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap">
                            @if($lead->next_action_at)
                                <span class="{{ $lead->next_action_at->isPast() ? 'font-semibold text-rose-600' : '' }}">{{ $lead->next_action_at->format('d/m/Y H:i') }}</span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td>
                            <div class="table-actions">
                                @can('view', $lead)
                                    <a class="table-action-link" href="{{ route('leads.show', $lead) }}">Ver</a>
                                @endcan
                                @can('update', $lead)
                                    <a class="table-action-link" href="{{ route('leads.edit', $lead) }}">Editar</a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <x-empty-state
                                :title="$hasFilters ? 'Nenhum lead encontrado.' : 'Nenhum lead cadastrado ainda.'"
                                :description="$hasFilters ? 'Revise ou limpe os filtros para ampliar os resultados.' : 'Os leads cadastrados aparecerão nesta lista.'"
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($leads->hasPages())
        <div class="listing-pagination">{{ $leads->links() }}</div>
    @endif
</x-layouts.app>
