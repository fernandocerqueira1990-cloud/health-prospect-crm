@php
    $roles = ['decision_maker' => 'Decisor', 'influencer' => 'Influenciador', 'champion' => 'Patrocinador interno', 'user' => 'Usuário', 'technical' => 'Técnico', 'procurement' => 'Compras', 'financial' => 'Financeiro', 'gatekeeper' => 'Guardião', 'blocker' => 'Bloqueador', 'other' => 'Outro'];
    $levels = ['low' => 'Baixa', 'medium' => 'Média', 'high' => 'Alta', 'critical' => 'Crítica'];
    $levelVariants = ['low' => 'neutral', 'medium' => 'info', 'high' => 'warning', 'critical' => 'danger'];
    $advancedFilters = ['name', 'job_title', 'department', 'email', 'phone', 'decision_role', 'influence_level', 'is_primary', 'sort', 'direction'];
    $hasAdvancedFilters = collect($advancedFilters)->contains(fn ($filter) => request()->filled($filter));
    $hasFilters = request()->except('page') !== [];
@endphp

<x-layouts.app title="Contatos">
    <x-page-header title="Contatos" description="Pessoas, decisores e influenciadores comerciais.">
        <x-slot:actions>
            @can('create', App\Models\Contact::class)
                <a class="btn-primary" href="{{ route('contacts.create') }}">Novo contato</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <form class="listing-filters" method="GET" action="{{ route('contacts.index') }}">
        <x-filter-bar class="mb-0 border-0 p-0 shadow-none">
            <div class="filter-field-primary"><label class="label" for="search">Busca geral</label><input class="input" id="search" name="search" value="{{ request('search') }}" placeholder="Nome, empresa, cargo, e-mail ou telefone"></div>
            <div class="filter-field-select"><label class="label" for="company">Empresa</label><select class="input" id="company" name="company"><option value="">Todas</option>@foreach($companies as $company)<option value="{{ $company->id }}" @selected((string) request('company') === (string) $company->id)>{{ $company->legal_name }}</option>@endforeach</select></div>
            <div class="filter-field-select"><label class="label" for="active">Status</label><select class="input" id="active" name="active"><option value="">Todos</option><option value="1" @selected(request('active') === '1')>Ativo</option><option value="0" @selected(request('active') === '0')>Inativo</option></select></div>
            <x-slot:actions><button class="btn-primary" type="submit">Filtrar</button></x-slot:actions>
        </x-filter-bar>

        <div class="listing-filter-footer">
            <details class="advanced-filters" @if($hasAdvancedFilters) open @endif>
                <summary>Filtros avançados</summary>
                <div class="advanced-filter-grid advanced-filter-grid-contacts">
                    <div><label class="label" for="name">Nome</label><input class="input" id="name" name="name" value="{{ request('name') }}"></div>
                    <div><label class="label" for="job_title">Cargo</label><input class="input" id="job_title" name="job_title" value="{{ request('job_title') }}"></div>
                    <div><label class="label" for="department">Departamento</label><input class="input" id="department" name="department" value="{{ request('department') }}"></div>
                    <div><label class="label" for="email">E-mail</label><input class="input" id="email" name="email" value="{{ request('email') }}"></div>
                    <div><label class="label" for="phone">Telefone</label><input class="input" id="phone" name="phone" value="{{ request('phone') }}"></div>
                    <div><label class="label" for="decision_role">Papel</label><select class="input" id="decision_role" name="decision_role"><option value="">Todos</option>@foreach($roles as $value => $label)<option value="{{ $value }}" @selected(request('decision_role') === $value)>{{ $label }}</option>@endforeach</select></div>
                    <div><label class="label" for="influence_level">Influência</label><select class="input" id="influence_level" name="influence_level"><option value="">Todas</option>@foreach($levels as $value => $label)<option value="{{ $value }}" @selected(request('influence_level') === $value)>{{ $label }}</option>@endforeach</select></div>
                    <div><label class="label" for="is_primary">Principal</label><select class="input" id="is_primary" name="is_primary"><option value="">Todos</option><option value="1" @selected(request('is_primary') === '1')>Sim</option><option value="0" @selected(request('is_primary') === '0')>Não</option></select></div>
                    <div><label class="label" for="sort">Ordenar</label><select class="input" id="sort" name="sort">@foreach(['created_at' => 'Criação', 'name' => 'Nome', 'job_title' => 'Cargo', 'department' => 'Departamento', 'updated_at' => 'Atualização'] as $value => $label)<option value="{{ $value }}" @selected(request('sort', 'created_at') === $value)>{{ $label }}</option>@endforeach</select></div>
                    <input type="hidden" name="direction" value="{{ request('direction', 'desc') }}">
                </div>
            </details>
            @if($hasFilters)<a class="filter-clear" href="{{ route('contacts.index') }}">Limpar filtros</a>@endif
        </div>
    </form>

    <div class="listing-summary">@if($contacts->total()) Mostrando {{ $contacts->firstItem() }}–{{ $contacts->lastItem() }} de {{ $contacts->total() }} registros @else Nenhum registro @endif</div>

    <div class="table-wrap">
        <table class="table listing-table">
            <thead><tr><th>Nome</th><th>Empresa</th><th>Cargo / departamento</th><th>Contato</th><th>Papel / influência</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($contacts as $contact)
                    <tr>
                        <td><a class="table-primary-link" href="{{ route('contacts.show', $contact) }}">{{ $contact->name }}</a>@if($contact->is_primary)<x-status-badge variant="warning" class="ml-1">Principal</x-status-badge>@endif</td>
                        <td>{{ $contact->company->trade_name ?: $contact->company->legal_name }}@if($contact->company->trashed())<span class="table-secondary-line font-semibold text-amber-700">Empresa arquivada</span>@endif</td>
                        <td>{{ collect([$contact->job_title, $contact->department])->filter()->join(' · ') ?: '—' }}</td>
                        <td><span class="break-words">{{ $contact->email ?: $contact->phone ?: '—' }}</span></td>
                        <td>@if($contact->decision_role)<span>{{ $roles[$contact->decision_role] ?? '—' }}</span>@else — @endif @if($contact->influence_level)<x-status-badge :variant="$levelVariants[$contact->influence_level] ?? 'neutral'" class="ml-1">{{ $levels[$contact->influence_level] ?? $contact->influence_level }}</x-status-badge>@endif</td>
                        <td><x-status-badge :variant="$contact->active ? 'success' : 'neutral'">{{ $contact->active ? 'Ativo' : 'Inativo' }}</x-status-badge></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty-state :title="$hasFilters ? 'Nenhum contato encontrado.' : 'Nenhum contato cadastrado ainda.'" :description="$hasFilters ? 'Revise ou limpe os filtros para ampliar os resultados.' : 'Os contatos cadastrados aparecerão nesta lista.'" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($contacts->hasPages())<div class="listing-pagination">{{ $contacts->links() }}</div>@endif
</x-layouts.app>
