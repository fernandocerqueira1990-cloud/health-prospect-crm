<x-layouts.app title="Dashboard">
    <section class="mb-5 overflow-hidden rounded-xl bg-slate-950 shadow-sm" aria-labelledby="dashboard-title">
        <div class="grid gap-4 px-5 py-5 sm:px-6 lg:grid-cols-[1.45fr_.55fr] lg:items-center lg:py-6">
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-teal-400">Central comercial</p>
                <h1 id="dashboard-title" class="mt-1.5 text-2xl font-bold tracking-tight text-white sm:text-3xl">
                    Olá, {{ str(auth()->user()->name)->before(' ') }}.
                </h1>
                <p class="mt-1.5 max-w-2xl text-sm leading-5 text-slate-300">Acompanhe os principais indicadores e acesse rapidamente sua operação comercial.</p>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-900/80 px-4 py-3">
                <div class="flex items-center gap-3">
                    <span class="status-dot" aria-hidden="true"></span>
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-wider text-teal-400">CRM operacional</p>
                        <p class="mt-0.5 text-sm leading-5 text-slate-300">Empresas, contatos, leads e pipeline em um só ambiente.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <x-metric-card
            label="Empresas"
            :value="$stats['companies'] ?? '—'"
            help="Organizações cadastradas no CRM"
        />
        <x-metric-card
            label="Contatos ativos"
            :value="$stats['active_contacts'] ?? '—'"
            help="Pessoas disponíveis para atuação comercial"
        />
        <x-metric-card
            label="Alta prioridade"
            :value="$stats['high_priority_companies'] ?? '—'"
            help="Empresas classificadas como alta ou crítica"
        />
        <x-metric-card
            label="Decisores / champions"
            :value="$stats['decision_contacts'] ?? '—'"
            help="Contatos ativos com influência estratégica"
        />
    </div>

    <section class="card mt-5 p-0" aria-labelledby="quick-access-title">
        <div class="border-b border-slate-200 px-4 py-3 sm:px-5">
            <h2 id="quick-access-title" class="text-base font-bold text-slate-950">Acesso rápido</h2>
            <p class="mt-0.5 text-sm text-slate-500">Atalhos para as ações mais frequentes da operação.</p>
        </div>
        <div class="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4">
            @can('create', App\Models\Company::class)
                <a href="{{ route('companies.create') }}" class="quick-action">
                    <span class="quick-action-icon" aria-hidden="true">+</span>
                    <span class="min-w-0"><strong>Nova empresa</strong><small>Cadastrar organização</small></span>
                </a>
            @endcan
            @can('create', App\Models\Contact::class)
                <a href="{{ route('contacts.create') }}" class="quick-action">
                    <span class="quick-action-icon" aria-hidden="true">+</span>
                    <span class="min-w-0"><strong>Novo contato</strong><small>Adicionar pessoa-chave</small></span>
                </a>
            @endcan
            @can('viewAny', App\Models\Lead::class)
                <a href="{{ route('leads.index') }}" class="quick-action">
                    <span class="quick-action-icon" aria-hidden="true">L</span>
                    <span class="min-w-0"><strong>Leads</strong><small>Acessar prospecção</small></span>
                </a>
            @endcan
            @can('viewAny', App\Models\Opportunity::class)
                <a href="{{ route('roadmap.pipeline') }}" class="quick-action">
                    <span class="quick-action-icon" aria-hidden="true">P</span>
                    <span class="min-w-0"><strong>Pipeline</strong><small>Visualizar oportunidades</small></span>
                </a>
            @endcan
        </div>
    </section>

    <div class="mt-5 grid gap-5 xl:grid-cols-2">
        @if($canViewCompanies)
            <section class="card p-0" aria-labelledby="recent-companies-title">
                <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-4 py-3 sm:px-5">
                    <div class="min-w-0">
                        <h2 id="recent-companies-title" class="text-base font-bold text-slate-950">Empresas recentes</h2>
                        <p class="text-sm text-slate-500">Últimos cadastros realizados.</p>
                    </div>
                    <a href="{{ route('companies.index') }}" class="shrink-0 text-sm font-bold text-teal-700 hover:text-teal-900">Ver todas</a>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($recentCompanies as $company)
                        <a href="{{ route('companies.show', $company) }}" class="flex items-center justify-between gap-4 px-4 py-3 transition hover:bg-slate-50 sm:px-5">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $company->trade_name ?: $company->legal_name }}</p>
                                <p class="mt-0.5 truncate text-xs text-slate-500">{{ collect([$company->city, $company->state])->filter()->join(' / ') ?: 'Localidade não informada' }}</p>
                            </div>
                            <span class="shrink-0 text-xs font-medium text-slate-400">{{ $company->created_at->format('d/m') }}</span>
                        </a>
                    @empty
                        <x-empty-state title="Nenhuma empresa cadastrada ainda." class="py-6" />
                    @endforelse
                </div>
            </section>
        @endif

        @if($canViewContacts)
            <section class="card p-0" aria-labelledby="recent-contacts-title">
                <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-4 py-3 sm:px-5">
                    <div class="min-w-0">
                        <h2 id="recent-contacts-title" class="text-base font-bold text-slate-950">Contatos recentes</h2>
                        <p class="text-sm text-slate-500">Pessoas adicionadas mais recentemente.</p>
                    </div>
                    <a href="{{ route('contacts.index') }}" class="shrink-0 text-sm font-bold text-teal-700 hover:text-teal-900">Ver todos</a>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($recentContacts as $contact)
                        <a href="{{ route('contacts.show', $contact) }}" class="flex items-center justify-between gap-4 px-4 py-3 transition hover:bg-slate-50 sm:px-5">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $contact->name }}</p>
                                <p class="mt-0.5 truncate text-xs text-slate-500">{{ $contact->company->trade_name ?: $contact->company->legal_name }}{{ $contact->job_title ? ' · '.$contact->job_title : '' }}</p>
                            </div>
                            <span class="shrink-0 text-xs font-medium {{ $contact->active ? 'text-emerald-600' : 'text-slate-400' }}">{{ $contact->active ? 'Ativo' : 'Inativo' }}</span>
                        </a>
                    @empty
                        <x-empty-state title="Nenhum contato cadastrado ainda." class="py-6" />
                    @endforelse
                </div>
            </section>
        @endif
    </div>
</x-layouts.app>
