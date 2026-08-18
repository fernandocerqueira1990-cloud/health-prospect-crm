@php
    $typeLabels = [
        'call' => 'Ligação',
        'email' => 'E-mail',
        'whatsapp' => 'WhatsApp',
        'meeting' => 'Reunião',
        'note' => 'Nota',
        'other' => 'Outro',
    ];
    $typeVariants = [
        'call' => 'info',
        'email' => 'info',
        'whatsapp' => 'success',
        'meeting' => 'warning',
        'note' => 'neutral',
        'other' => 'neutral',
    ];
    $advancedFilters = ['type', 'direction', 'date_from', 'date_to', 'per_page'];
    $hasAdvancedFilters = collect($advancedFilters)->contains(fn ($filter) => request()->filled($filter));
    $hasFilters = request()->except('page') !== [];
@endphp

<x-layouts.app title="Atividades">
    <x-page-header title="Atividades" description="Interações, follow-ups e ações comerciais.">
        <x-slot:actions>
            @can('create', App\Models\Activity::class)
                <a class="btn-primary" href="{{ route('activities.create') }}">Nova atividade</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <form class="listing-filters" method="GET" action="{{ route('activities.index') }}">
        <x-filter-bar class="mb-0 border-0 p-0 shadow-none">
            <div class="filter-field-primary">
                <label class="label" for="q">Busca geral</label>
                <input class="input" id="q" name="q" value="{{ request('q') }}" placeholder="Assunto, descrição, empresa, lead...">
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
                        <label class="label" for="type">Tipo</label>
                        <select class="input" id="type" name="type">
                            <option value="">Todos</option>
                            @foreach($typeLabels as $value => $label)
                                <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label" for="direction">Direção</label>
                        <select class="input" id="direction" name="direction">
                            <option value="">Todas</option>
                            <option value="outbound" @selected(request('direction') === 'outbound')>Saída</option>
                            <option value="inbound" @selected(request('direction') === 'inbound')>Entrada</option>
                        </select>
                    </div>
                    <div><label class="label" for="date_from">De</label><input class="input" id="date_from" name="date_from" type="date" value="{{ request('date_from') }}"></div>
                    <div><label class="label" for="date_to">Até</label><input class="input" id="date_to" name="date_to" type="date" value="{{ request('date_to') }}"></div>
                    <div>
                        <label class="label" for="per_page">Por página</label>
                        <select class="input" id="per_page" name="per_page">
                            @foreach([15, 30, 50, 100] as $value)
                                <option value="{{ $value }}" @selected((int) request('per_page', 15) === $value)>{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </details>
            @if($hasFilters)
                <a class="filter-clear" href="{{ route('activities.index') }}">Limpar filtros</a>
            @endif
        </div>
    </form>

    <div class="listing-summary">
        @if($activities->total())
            Mostrando {{ $activities->firstItem() }}–{{ $activities->lastItem() }} de {{ $activities->total() }} atividades
        @else
            Nenhuma atividade encontrada
        @endif
    </div>

    <div class="table-wrap">
        <table class="table listing-table">
            <thead>
                <tr>
                    <th>Atividade</th>
                    <th>Tipo</th>
                    <th>Vínculo</th>
                    <th>Responsável</th>
                    <th>Data / duração</th>
                    <th>Resultado</th>
                    <th class="text-right">AÇÕES</th>
                </tr>
            </thead>
            <tbody>
                @forelse($activities as $activity)
                    <tr>
                        <td><a class="table-primary-link" href="{{ route('activities.show', $activity) }}">{{ $activity->subject }}</a></td>
                        <td>
                            <x-status-badge :variant="$typeVariants[$activity->type] ?? 'neutral'">{{ $typeLabels[$activity->type] ?? $activity->type }}</x-status-badge>
                            @if($activity->direction === 'outbound')
                                <span class="table-secondary-line">Saída</span>
                            @elseif($activity->direction === 'inbound')
                                <span class="table-secondary-line">Entrada</span>
                            @endif
                        </td>
                        <td>
                            @if($activity->company)
                                <span class="font-medium text-slate-800">{{ $activity->company->trade_name ?: $activity->company->legal_name }}</span><span class="table-secondary-line">Empresa</span>
                            @elseif($activity->opportunity)
                                <span class="font-medium text-slate-800">{{ $activity->opportunity->title }}</span><span class="table-secondary-line">Oportunidade</span>
                            @elseif($activity->lead)
                                <span class="font-medium text-slate-800">{{ $activity->lead->name ?: $activity->lead->company_name ?: 'Lead #'.$activity->lead->id }}</span><span class="table-secondary-line">Lead</span>
                            @elseif($activity->contact)
                                <span class="font-medium text-slate-800">{{ $activity->contact->name }}</span><span class="table-secondary-line">Contato</span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td>{{ $activity->assignedUser?->name ?? 'Não atribuído' }}</td>
                        <td class="whitespace-nowrap">
                            {{ $activity->occurred_at?->format('d/m/Y H:i') ?? '—' }}
                            @if($activity->duration_minutes)
                                <span class="table-secondary-line">{{ $activity->duration_minutes }} min</span>
                            @endif
                        </td>
                        <td>
                            @if($activity->outcome)
                                <span class="block max-w-64 truncate" title="{{ $activity->outcome }}">{{ $activity->outcome }}</span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td>
                            <div class="table-actions">
                                @can('view', $activity)<a class="table-action-link" href="{{ route('activities.show', $activity) }}">Ver</a>@endcan
                                @can('update', $activity)<a class="table-action-link" href="{{ route('activities.edit', $activity) }}">Editar</a>@endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <x-empty-state :title="$hasFilters ? 'Nenhuma atividade encontrada.' : 'Nenhuma atividade cadastrada ainda.'" :description="$hasFilters ? 'Revise ou limpe os filtros para ampliar os resultados.' : 'As atividades cadastradas aparecerão nesta lista.'" />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($activities->hasPages())<div class="listing-pagination">{{ $activities->links() }}</div>@endif
</x-layouts.app>
