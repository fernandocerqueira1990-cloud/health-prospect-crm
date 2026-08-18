@php
    $statusLabels = ['draft' => 'Rascunho', 'planned' => 'Planejada', 'active' => 'Ativa', 'paused' => 'Pausada', 'completed' => 'Concluída', 'cancelled' => 'Cancelada'];
    $statusVariants = ['draft' => 'neutral', 'planned' => 'info', 'active' => 'success', 'paused' => 'warning', 'completed' => 'success', 'cancelled' => 'danger'];
    $advancedFilters = ['start_date_to', 'end_date_from', 'end_date_to', 'sort', 'direction'];
    $hasAdvancedFilters = collect($advancedFilters)->contains(fn ($filter) => request()->filled($filter));
    $hasFilters = request()->except('page') !== [];
@endphp

<x-layouts.app title="Campanhas">
    <x-page-header title="Campanhas" description="Planejamento de campanhas, canais e parâmetros de aquisição.">
        <x-slot:actions>
            @can('create', App\Models\Campaign::class)
                <a class="btn-primary" href="{{ route('campaigns.create') }}">Nova campanha</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <form class="listing-filters" method="GET" action="{{ route('campaigns.index') }}">
        <x-filter-bar class="mb-0 border-0 p-0 shadow-none">
            <div class="filter-field-primary">
                <label class="label" for="q">Buscar campanha</label>
                <input class="input" id="q" name="q" value="{{ request('q') }}" placeholder="Nome, descrição ou parâmetros UTM">
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
                <label class="label" for="channel_id">Canal</label>
                <select class="input" id="channel_id" name="channel_id">
                    <option value="">Todos</option>
                    @foreach($channels as $channel)
                        <option value="{{ $channel->id }}" @selected((string) request('channel_id') === (string) $channel->id)>{{ $channel->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field-select">
                <label class="label" for="owner_user_id">Responsável</label>
                <select class="input" id="owner_user_id" name="owner_user_id">
                    <option value="">Todos</option>
                    @foreach($owners as $owner)
                        <option value="{{ $owner->id }}" @selected((string) request('owner_user_id') === (string) $owner->id)>{{ $owner->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field-select">
                <label class="label" for="start_date_from">Data início de</label>
                <input class="input" id="start_date_from" name="start_date_from" type="date" value="{{ request('start_date_from') }}">
            </div>
            <x-slot:actions><button class="btn-primary" type="submit">Filtrar</button></x-slot:actions>
        </x-filter-bar>

        <div class="listing-filter-footer">
            <details class="advanced-filters" @if($hasAdvancedFilters) open @endif>
                <summary>Filtros avançados</summary>
                <div class="advanced-filter-grid">
                    <div><label class="label" for="start_date_to">Data início até</label><input class="input" id="start_date_to" name="start_date_to" type="date" value="{{ request('start_date_to') }}"></div>
                    <div><label class="label" for="end_date_from">Data término de</label><input class="input" id="end_date_from" name="end_date_from" type="date" value="{{ request('end_date_from') }}"></div>
                    <div><label class="label" for="end_date_to">Data término até</label><input class="input" id="end_date_to" name="end_date_to" type="date" value="{{ request('end_date_to') }}"></div>
                    <div><label class="label" for="sort">Ordenar por</label><select class="input" id="sort" name="sort">@foreach(['created_at' => 'Criação', 'name' => 'Nome', 'status' => 'Status', 'start_date' => 'Data de início', 'end_date' => 'Data de término', 'budget' => 'Orçamento'] as $value => $label)<option value="{{ $value }}" @selected(request('sort', 'created_at') === $value)>{{ $label }}</option>@endforeach</select></div>
                    <div><label class="label" for="direction">Direção</label><select class="input" id="direction" name="direction"><option value="desc" @selected(request('direction', 'desc') === 'desc')>Decrescente</option><option value="asc" @selected(request('direction') === 'asc')>Crescente</option></select></div>
                </div>
            </details>
            @if($hasFilters)<a class="filter-clear" href="{{ route('campaigns.index') }}">Limpar filtros</a>@endif
        </div>
    </form>

    <div class="listing-summary">
        @if($campaigns->total()) Mostrando {{ $campaigns->firstItem() }}–{{ $campaigns->lastItem() }} de {{ $campaigns->total() }} campanhas @else Nenhuma campanha encontrada @endif
    </div>
    <div class="table-wrap">
        <table class="table listing-table">
            <thead><tr><th>Nome</th><th>Status</th><th>Canal</th><th>Responsável</th><th>Período</th><th>Orçamento</th><th class="text-right">AÇÕES</th></tr></thead>
            <tbody>
                @forelse($campaigns as $campaign)
                    <tr>
                        <td class="max-w-80"><a class="table-primary-link break-words" href="{{ route('campaigns.show', $campaign) }}">{{ $campaign->name }}</a>@if($campaign->description)<span class="table-secondary-line max-w-72 truncate" title="{{ $campaign->description }}">{{ $campaign->description }}</span>@endif</td>
                        <td><x-status-badge :variant="$statusVariants[$campaign->status] ?? 'neutral'">{{ $statusLabels[$campaign->status] ?? $campaign->status }}</x-status-badge></td>
                        <td>{{ $campaign->channel?->name ?? '—' }}</td>
                        <td>{{ $campaign->owner?->name ?? 'Não atribuído' }}</td>
                        <td class="whitespace-nowrap">@if($campaign->start_date || $campaign->end_date){{ $campaign->start_date?->format('d/m/Y') ?? '…' }} – {{ $campaign->end_date?->format('d/m/Y') ?? '…' }}@else — @endif</td>
                        <td class="whitespace-nowrap">{{ $campaign->budget !== null ? $campaign->currency.' '.number_format((float) $campaign->budget, 2, ',', '.') : '—' }}</td>
                        <td><div class="table-actions">@can('view', $campaign)<a class="table-action-link" href="{{ route('campaigns.show', $campaign) }}">Ver</a>@endcan @can('update', $campaign)<a class="table-action-link" href="{{ route('campaigns.edit', $campaign) }}">Editar</a>@endcan</div></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty-state :title="$hasFilters ? 'Nenhuma campanha encontrada para os filtros selecionados.' : 'Nenhuma campanha cadastrada ainda.'" :description="$hasFilters ? 'Revise ou limpe os filtros para ampliar os resultados.' : 'As campanhas cadastradas aparecerão nesta lista.'" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($campaigns->hasPages())<div class="listing-pagination">{{ $campaigns->links() }}</div>@endif
</x-layouts.app>
