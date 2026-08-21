<x-layouts.app :title="$company->legal_name">
    <x-page-header :title="$company->legal_name" description="Detalhes da organização.">
        <x-slot:actions><div class="flex flex-wrap gap-2">@can('update', $company)<a class="btn-primary" href="{{ route('companies.edit', $company) }}">Editar</a>@endcan<a class="btn-secondary" href="{{ route('companies.index') }}">Voltar</a></div></x-slot:actions>
    </x-page-header>

    <nav class="mb-4 flex gap-1 overflow-x-auto border-b border-slate-200" aria-label="Seções da empresa">
        <span class="whitespace-nowrap border-b-2 border-teal-700 px-4 py-2.5 text-sm font-semibold text-teal-800">Resumo</span>
        @can('viewAny', App\Models\Contact::class)<a class="whitespace-nowrap px-4 py-2.5 text-sm font-medium text-teal-700" href="#contatos">Contatos ({{ $contacts->total() }})</a>@endcan
        @foreach(['Leads', 'Oportunidades', 'Atividades'] as $futureTab)<span class="cursor-not-allowed whitespace-nowrap px-4 py-2.5 text-sm text-slate-400" title="Disponível em sprint futura">{{ $futureTab }}</span>@endforeach
    </nav>

    @php($priorityLabels = ['low' => 'Baixa', 'medium' => 'Média', 'high' => 'Alta', 'critical' => 'Crítica'])
    <div class="grid gap-4 xl:grid-cols-3">
        <div class="space-y-4 xl:col-span-2">
            <x-form-section title="Identificação">
                <dl class="detail-grid lg:grid-cols-3">
                    <div class="detail-item lg:col-span-2"><dt class="detail-label">Razão social</dt><dd class="detail-value">{{ $company->legal_name }}</dd></div>
                    <div class="detail-item"><dt class="detail-label">Nome fantasia</dt><dd class="detail-value">{{ $company->trade_name ?? '—' }}</dd></div>
                    <div class="detail-item"><dt class="detail-label">CNPJ / ID fiscal</dt><dd class="detail-value">{{ $company->formattedTaxId() ?? '—' }}{{ $company->tax_id_country ? ' · '.$company->tax_id_country : '' }}</dd></div>
                    <div class="detail-item"><dt class="detail-label">Segmento</dt><dd class="detail-value">{{ $company->segment ?? '—' }}</dd></div>
                    <div class="detail-item"><dt class="detail-label">Categoria</dt><dd class="detail-value">{{ $company->category ?? '—' }}</dd></div>
                </dl>
            </x-form-section>

            <div class="grid gap-4 lg:grid-cols-2">
                <x-form-section title="Contato">
                    <dl class="detail-grid lg:grid-cols-1">
                        <div class="detail-item"><dt class="detail-label">E-mail</dt><dd class="detail-value">@if($company->email)<a class="text-teal-700 hover:underline" href="mailto:{{ $company->email }}">{{ $company->email }}</a>@else — @endif</dd></div>
                        <div class="detail-item"><dt class="detail-label">Telefone</dt><dd class="detail-value">@if($company->phone)<a class="text-teal-700 hover:underline" href="tel:{{ $company->phone }}">{{ $company->phone }}</a>@else — @endif</dd></div>
                        <div class="detail-item"><dt class="detail-label">Website</dt><dd class="detail-value">@if($company->website)<a class="text-teal-700 hover:underline" href="{{ $company->website }}" rel="noopener noreferrer" target="_blank">{{ $company->website }}</a>@else — @endif</dd></div>
                    </dl>
                </x-form-section>
                <x-form-section title="Localização">
                    <dl class="detail-grid lg:grid-cols-1">
                        <div class="detail-item"><dt class="detail-label">Endereço</dt><dd class="detail-value">{{ collect([$company->street, $company->number, $company->complement])->filter()->join(', ') ?: '—' }}</dd></div>
                        <div class="detail-item"><dt class="detail-label">Bairro</dt><dd class="detail-value">{{ $company->district ?? '—' }}</dd></div>
                        <div class="detail-item"><dt class="detail-label">Cidade / Estado</dt><dd class="detail-value">{{ collect([$company->city, $company->state])->filter()->join(' / ') ?: '—' }}</dd></div>
                        <div class="detail-item"><dt class="detail-label">CEP / Código postal</dt><dd class="detail-value">{{ $company->postal_code ?? '—' }}</dd></div>
                    </dl>
                </x-form-section>
            </div>

            <x-form-section title="Observações">
                <p class="whitespace-pre-line text-sm leading-6 text-slate-700">{{ $company->notes ?? 'Nenhuma observação registrada.' }}</p>
            </x-form-section>

            @can('viewAny', App\Models\Contact::class)
                <section class="card" id="contatos">
                    <div class="card-header"><div><h2 class="card-title">Contatos</h2><p class="card-subtitle">Pessoas vinculadas à organização.</p></div>@can('create', App\Models\Contact::class)<a class="btn-primary" href="{{ route('contacts.create',['company_id'=>$company->id]) }}">Novo contato</a>@endcan</div>
                    <div class="overflow-x-auto"><table class="table"><thead><tr><th>Nome</th><th>Cargo / departamento</th><th>Contato</th><th>Relacionamento</th><th>Status</th></tr></thead><tbody>@forelse($contacts as $contact)<tr><td><a class="font-semibold text-teal-700" href="{{ route('contacts.show',$contact) }}">{{ $contact->name }}</a>@if($contact->is_primary) <x-status-badge variant="info">Principal</x-status-badge> @endif</td><td>{{ collect([$contact->job_title,$contact->department])->filter()->join(' · ') ?: '—' }}</td><td>{{ $contact->email ?: $contact->phone ?: $contact->whatsapp ?: '—' }}</td><td>{{ $contact->decision_role ?? '—' }} · {{ $contact->influence_level ?? '—' }}</td><td><x-status-badge :variant="$contact->active ? 'success' : 'neutral'">{{ $contact->active ? 'Ativo' : 'Inativo' }}</x-status-badge></td></tr>@empty<tr><td colspan="5">Nenhum contato cadastrado.</td></tr>@endforelse</tbody></table></div>
                    <div class="mt-4">{{ $contacts->links() }}</div>
                </section>
            @endcan
        </div>

        <aside class="space-y-4">
            <x-form-section title="Comercial">
                <dl class="detail-grid xl:grid-cols-1">
                    <div class="detail-item"><dt class="detail-label">Responsável</dt><dd class="detail-value">{{ $company->assignedUser?->name ?? 'Não atribuído' }}</dd></div>
                    <div class="detail-item"><dt class="detail-label">Prioridade</dt><dd class="detail-value"><x-status-badge :variant="$company->priority === 'critical' ? 'danger' : ($company->priority === 'high' ? 'warning' : 'neutral')">{{ $priorityLabels[$company->priority] ?? 'Não definida' }}</x-status-badge></dd></div>
                    <div class="detail-item"><dt class="detail-label">Funcionários estimados</dt><dd class="detail-value">{{ $company->employee_count_estimate ?? '—' }}</dd></div>
                </dl>
            </x-form-section>
            <x-form-section title="Metadados">
                <dl><div class="detail-item"><dt class="detail-label">Criada em</dt><dd class="detail-value">{{ $company->created_at->format('d/m/Y H:i') }}</dd></div></dl>
            </x-form-section>
            @can('delete', $company)<section class="rounded-xl border border-red-200 bg-white p-4 shadow-sm"><h2 class="font-semibold text-red-900">Excluir empresa</h2><p class="mt-1 text-sm text-slate-600">A empresa será arquivada por soft delete e não será removida definitivamente.</p><form class="mt-3" method="POST" action="{{ route('companies.destroy', $company) }}" data-confirm="Confirma a exclusão desta empresa?">@csrf @method('DELETE')<button class="btn-danger" type="submit">Excluir empresa</button></form></section>@endcan
        </aside>
    </div>
</x-layouts.app>
