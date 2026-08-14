<x-layouts.app :title="$company->legal_name">
    <x-page-header :title="$company->legal_name" :description="$company->trade_name ?: 'Visão 360 da empresa'">
        <x-slot:actions><div class="flex flex-wrap gap-3">@can('update', $company)<a class="btn-primary" href="{{ route('companies.edit', $company) }}">Editar</a>@endcan<a class="btn-secondary" href="{{ route('companies.index') }}">Voltar</a></div></x-slot:actions>
    </x-page-header>

    <nav class="mb-6 flex gap-1 overflow-x-auto border-b border-slate-200" aria-label="Seções da empresa">
        <span class="border-b-2 border-teal-700 px-4 py-3 text-sm font-semibold text-teal-800">Resumo</span>
        @can('viewAny', App\Models\Contact::class)<a class="px-4 py-3 text-sm font-medium text-teal-700" href="#contatos">Contatos ({{ $contacts->total() }})</a>@endcan
        @foreach(['Leads', 'Oportunidades', 'Atividades'] as $futureTab)<span class="cursor-not-allowed px-4 py-3 text-sm text-slate-400" title="Disponível em sprint futura">{{ $futureTab }}</span>@endforeach
    </nav>

    @php($priorityLabels = ['low' => 'Baixa', 'medium' => 'Média', 'high' => 'Alta', 'critical' => 'Crítica'])
    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <section class="card"><h2 class="text-base font-semibold text-slate-900">Dados principais</h2><dl class="mt-4 grid gap-5 sm:grid-cols-2">
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Razão social</dt><dd class="mt-1">{{ $company->legal_name }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Nome fantasia</dt><dd class="mt-1">{{ $company->trade_name ?? '—' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">CNPJ / ID fiscal</dt><dd class="mt-1">{{ $company->formattedTaxId() ?? '—' }}{{ $company->tax_id_country ? ' · '.$company->tax_id_country : '' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Segmento / categoria</dt><dd class="mt-1">{{ collect([$company->segment, $company->category])->filter()->join(' · ') ?: '—' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Funcionários estimados</dt><dd class="mt-1">{{ $company->employee_count_estimate ?? '—' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Criada em</dt><dd class="mt-1">{{ $company->created_at->format('d/m/Y H:i') }}</dd></div>
            </dl></section>
            <section class="card"><h2 class="text-base font-semibold text-slate-900">Contato e endereço</h2><dl class="mt-4 grid gap-5 sm:grid-cols-2">
                <div><dt class="text-xs font-semibold uppercase text-slate-500">E-mail</dt><dd class="mt-1">{{ $company->email ?? '—' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Telefone</dt><dd class="mt-1">{{ $company->phone ?? '—' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Website</dt><dd class="mt-1 break-all">@if($company->website)<a class="text-teal-700 hover:underline" href="{{ $company->website }}" rel="noopener noreferrer" target="_blank">{{ $company->website }}</a>@else — @endif</dd></div>
                <div class="sm:col-span-2"><dt class="text-xs font-semibold uppercase text-slate-500">Endereço</dt><dd class="mt-1">{{ collect([$company->street, $company->number, $company->complement, $company->district, $company->city, $company->state, $company->postal_code])->filter()->join(', ') ?: '—' }}</dd></div>
            </dl></section>
            <section class="card"><h2 class="text-base font-semibold text-slate-900">Observações</h2><p class="mt-4 whitespace-pre-line text-sm text-slate-700">{{ $company->notes ?? 'Nenhuma observação registrada.' }}</p></section>
            @can('viewAny', App\Models\Contact::class)<section class="card" id="contatos"><div class="flex items-center justify-between"><h2 class="text-base font-semibold text-slate-900">Contatos</h2>@can('create', App\Models\Contact::class)<a class="btn-primary" href="{{ route('contacts.create',['company_id'=>$company->id]) }}">Novo contato</a>@endcan</div><div class="mt-4 overflow-x-auto"><table class="table"><thead><tr><th>Nome</th><th>Cargo / departamento</th><th>Contato</th><th>Relacionamento</th><th>Status</th></tr></thead><tbody>@forelse($contacts as $contact)<tr><td><a class="font-semibold text-teal-700" href="{{ route('contacts.show',$contact) }}">{{ $contact->name }}</a>@if($contact->is_primary) · Principal @endif</td><td>{{ collect([$contact->job_title,$contact->department])->filter()->join(' · ') ?: '—' }}</td><td>{{ $contact->email ?: $contact->phone ?: $contact->whatsapp ?: '—' }}</td><td>{{ $contact->decision_role ?? '—' }} · {{ $contact->influence_level ?? '—' }}</td><td>{{ $contact->active ? 'Ativo' : 'Inativo' }}</td></tr>@empty<tr><td colspan="5">Nenhum contato cadastrado.</td></tr>@endforelse</tbody></table></div><div class="mt-5">{{ $contacts->links() }}</div></section>@endcan
        </div>
        <aside class="space-y-6">
            <section class="card"><h2 class="text-base font-semibold text-slate-900">Comercial</h2><dl class="mt-4 space-y-4">
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Responsável</dt><dd class="mt-1">{{ $company->assignedUser?->name ?? 'Não atribuído' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Prioridade</dt><dd class="mt-1">{{ $priorityLabels[$company->priority] ?? 'Não definida' }}</dd></div>
            </dl></section>
            @can('delete', $company)<section class="rounded-xl border border-red-200 bg-white p-5"><h2 class="font-semibold text-red-900">Excluir empresa</h2><p class="mt-2 text-sm text-slate-600">A empresa será arquivada por soft delete e não será removida definitivamente.</p><form class="mt-4" method="POST" action="{{ route('companies.destroy', $company) }}" onsubmit="return confirm('Confirma a exclusão desta empresa?')">@csrf @method('DELETE')<button class="inline-flex rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-800" type="submit">Excluir empresa</button></form></section>@endcan
        </aside>
    </div>
</x-layouts.app>
