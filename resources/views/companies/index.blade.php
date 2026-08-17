@php
    $priorityLabels = ['low' => 'Baixa', 'medium' => 'Média', 'high' => 'Alta', 'critical' => 'Crítica'];
    $priorityVariants = ['low' => 'neutral', 'medium' => 'info', 'high' => 'warning', 'critical' => 'danger'];
    $advancedFilters = ['legal_name', 'trade_name', 'tax_id', 'segment', 'category', 'city', 'state', 'district', 'created_from', 'created_to', 'sort', 'direction'];
    $hasAdvancedFilters = collect($advancedFilters)->contains(fn ($filter) => request()->filled($filter));
    $hasFilters = request()->except('page') !== [];
@endphp

<x-layouts.app title="Empresas">
    <x-page-header title="Empresas" description="Organizações comerciais, clientes e potenciais clientes.">
        <x-slot:actions>
            @can('create', App\Models\Company::class)
                <a class="btn-primary" href="{{ route('companies.create') }}">Nova empresa</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <form class="listing-filters" method="GET" action="{{ route('companies.index') }}">
        <x-filter-bar class="mb-0 border-0 p-0 shadow-none">
            <div class="filter-field-primary">
                <label class="label" for="search">Busca geral</label>
                <input class="input" id="search" name="search" value="{{ request('search') }}" placeholder="Nome, CNPJ, e-mail, telefone ou localidade">
            </div>
            <div class="filter-field-select">
                <label class="label" for="assigned_user">Responsável</label>
                <select class="input" id="assigned_user" name="assigned_user">
                    <option value="">Todos</option>
                    @foreach($assignedUsers as $assignedUser)
                        <option value="{{ $assignedUser->id }}" @selected((string) request('assigned_user') === (string) $assignedUser->id)>{{ $assignedUser->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field-select">
                <label class="label" for="priority">Prioridade</label>
                <select class="input" id="priority" name="priority">
                    <option value="">Todas</option>
                    @foreach($priorityLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('priority') === $value)>{{ $label }}</option>
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
                    <div><label class="label" for="legal_name">Razão social</label><input class="input" id="legal_name" name="legal_name" value="{{ request('legal_name') }}"></div>
                    <div><label class="label" for="trade_name">Nome fantasia</label><input class="input" id="trade_name" name="trade_name" value="{{ request('trade_name') }}"></div>
                    <div><label class="label" for="tax_id">CNPJ / ID fiscal</label><input class="input" id="tax_id" name="tax_id" value="{{ request('tax_id') }}"></div>
                    <div><label class="label" for="segment">Segmento</label><input class="input" id="segment" name="segment" value="{{ request('segment') }}"></div>
                    <div><label class="label" for="category">Categoria</label><input class="input" id="category" name="category" value="{{ request('category') }}"></div>
                    <div><label class="label" for="city">Cidade</label><input class="input" id="city" name="city" value="{{ request('city') }}"></div>
                    <div><label class="label" for="state">Estado / UF</label><input class="input" id="state" name="state" value="{{ request('state') }}"></div>
                    <div><label class="label" for="district">Bairro</label><input class="input" id="district" name="district" value="{{ request('district') }}"></div>
                    <div><label class="label" for="created_from">Criada a partir de</label><input class="input" id="created_from" name="created_from" type="date" value="{{ request('created_from') }}"></div>
                    <div><label class="label" for="created_to">Criada até</label><input class="input" id="created_to" name="created_to" type="date" value="{{ request('created_to') }}"></div>
                    <div><label class="label" for="sort">Ordenar por</label><select class="input" id="sort" name="sort">@foreach(['created_at' => 'Criação', 'updated_at' => 'Atualização', 'legal_name' => 'Razão social', 'trade_name' => 'Nome fantasia', 'city' => 'Cidade', 'state' => 'Estado', 'priority' => 'Prioridade'] as $value => $label)<option value="{{ $value }}" @selected(request('sort', 'created_at') === $value)>{{ $label }}</option>@endforeach</select></div>
                    <div><label class="label" for="direction">Direção</label><select class="input" id="direction" name="direction"><option value="desc" @selected(request('direction', 'desc') === 'desc')>Decrescente</option><option value="asc" @selected(request('direction') === 'asc')>Crescente</option></select></div>
                </div>
            </details>
            @if($hasFilters)
                <a class="filter-clear" href="{{ route('companies.index') }}">Limpar filtros</a>
            @endif
        </div>
    </form>

    <div class="listing-summary">
        @if($companies->total())
            Mostrando {{ $companies->firstItem() }}–{{ $companies->lastItem() }} de {{ $companies->total() }} registros
        @else
            Nenhum registro
        @endif
    </div>

    <div class="table-wrap">
        <table class="table listing-table">
            <thead><tr><th>Empresa</th><th>CNPJ / ID fiscal</th><th>Localidade</th><th>Responsável</th><th>Prioridade</th><th class="text-right">AÇÕES</th></tr></thead>
            <tbody>
                @forelse($companies as $company)
                    <tr>
                        <td><a class="table-primary-link" href="{{ route('companies.show', $company) }}">{{ $company->legal_name }}</a>@if($company->trade_name)<span class="table-secondary-line">{{ $company->trade_name }}</span>@endif</td>
                        <td><span class="whitespace-nowrap">{{ $company->formattedTaxId() ?? '—' }}</span>@if($company->tax_id_country)<span class="table-secondary-line">{{ $company->tax_id_country }}</span>@endif</td>
                        <td class="whitespace-nowrap">{{ collect([$company->city, $company->state])->filter()->join('/') ?: '—' }}</td>
                        <td>{{ $company->assignedUser?->name ?? 'Não atribuído' }}</td>
                        <td>@if($company->priority)<x-status-badge :variant="$priorityVariants[$company->priority] ?? 'neutral'">{{ $priorityLabels[$company->priority] }}</x-status-badge>@else — @endif</td>
                        <td><div class="table-actions">@can('view', $company)<a class="table-action-link" href="{{ route('companies.show', $company) }}">Ver</a>@endcan @can('update', $company)<a class="table-action-link" href="{{ route('companies.edit', $company) }}">Editar</a>@endcan</div></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty-state :title="$hasFilters ? 'Nenhuma empresa encontrada.' : 'Nenhuma empresa cadastrada ainda.'" :description="$hasFilters ? 'Revise ou limpe os filtros para ampliar os resultados.' : 'As empresas cadastradas aparecerão nesta lista.'" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($companies->hasPages())<div class="listing-pagination">{{ $companies->links() }}</div>@endif
</x-layouts.app>
