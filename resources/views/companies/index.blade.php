<x-layouts.app title="Empresas">
    <x-page-header title="Empresas" description="Organizações comerciais, clientes e potenciais clientes.">
        <x-slot:actions>@can('create', App\Models\Company::class)<a class="btn-primary" href="{{ route('companies.create') }}">Nova empresa</a>@endcan</x-slot:actions>
    </x-page-header>

    <form class="card mb-6" method="GET" action="{{ route('companies.index') }}">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="sm:col-span-2"><label class="label" for="search">Busca geral</label><input class="input" id="search" name="search" value="{{ request('search') }}" placeholder="Nome, CNPJ, e-mail, telefone ou localidade"></div>
            <div><label class="label" for="assigned_user">Responsável</label><select class="input" id="assigned_user" name="assigned_user"><option value="">Todos</option>@foreach($assignedUsers as $assignedUser)<option value="{{ $assignedUser->id }}" @selected((string) request('assigned_user') === (string) $assignedUser->id)>{{ $assignedUser->name }}</option>@endforeach</select></div>
            <div><label class="label" for="priority">Prioridade</label><select class="input" id="priority" name="priority"><option value="">Todas</option>@foreach(['low' => 'Baixa', 'medium' => 'Média', 'high' => 'Alta', 'critical' => 'Crítica'] as $value => $label)<option value="{{ $value }}" @selected(request('priority') === $value)>{{ $label }}</option>@endforeach</select></div>
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
        <div class="mt-5 flex flex-wrap gap-3"><button class="btn-primary" type="submit">Filtrar</button><a class="btn-secondary" href="{{ route('companies.index') }}">Limpar</a></div>
    </form>

    @php($priorityClasses = ['low' => 'bg-slate-100 text-slate-700', 'medium' => 'bg-blue-100 text-blue-800', 'high' => 'bg-amber-100 text-amber-800', 'critical' => 'bg-red-100 text-red-800'])
    @php($priorityLabels = ['low' => 'Baixa', 'medium' => 'Média', 'high' => 'Alta', 'critical' => 'Crítica'])
    <div class="table-wrap"><table class="table"><thead><tr><th>Empresa</th><th>CNPJ / ID fiscal</th><th>Localidade</th><th>Responsável</th><th>Prioridade</th><th></th></tr></thead><tbody>
        @forelse($companies as $company)<tr>
            <td><a class="font-semibold text-teal-700 hover:text-teal-900" href="{{ route('companies.show', $company) }}">{{ $company->legal_name }}</a>@if($company->trade_name)<br><span class="text-slate-500">{{ $company->trade_name }}</span>@endif</td>
            <td>{{ $company->formattedTaxId() ?? '—' }}@if($company->tax_id_country)<br><span class="text-xs text-slate-500">{{ $company->tax_id_country }}</span>@endif</td>
            <td>{{ collect([$company->city, $company->state])->filter()->join('/') ?: '—' }}</td>
            <td>{{ $company->assignedUser?->name ?? 'Não atribuído' }}</td>
            <td>@if($company->priority)<span class="rounded-full px-2 py-1 text-xs font-semibold {{ $priorityClasses[$company->priority] }}">{{ $priorityLabels[$company->priority] }}</span>@else — @endif</td>
            <td class="text-right"><div class="flex justify-end gap-3">@can('view', $company)<a class="font-semibold text-teal-700 hover:text-teal-900" href="{{ route('companies.show', $company) }}">Ver</a>@endcan @can('update', $company)<a class="font-semibold text-teal-700 hover:text-teal-900" href="{{ route('companies.edit', $company) }}">Editar</a>@endcan</div></td>
        </tr>@empty<tr><td colspan="6" class="text-center">Nenhuma empresa encontrada.</td></tr>@endforelse
    </tbody></table></div>
    <div class="mt-5">{{ $companies->links() }}</div>
</x-layouts.app>
