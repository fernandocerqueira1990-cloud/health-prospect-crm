<x-layouts.app title="Dashboard">
    <div class="mb-7 overflow-hidden rounded-2xl bg-slate-950 shadow-sm">
        <div class="grid gap-6 px-6 py-7 sm:px-8 lg:grid-cols-[1.4fr_.6fr] lg:items-center">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-400">Central comercial</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-white sm:text-3xl">Olá, {{ str(auth()->user()->name)->before(' ') }}.</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300">A base do CRM está pronta para validação visual. Empresas e Contatos estão operacionais; o módulo de Leads está em implementação na Sprint 4.</p>
            </div>
            <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Sprint atual</p>
                        <p class="mt-1 font-bold text-white">Sprint 4 — Leads</p>
                    </div>
                    <span class="status-dot"></span>
                </div>
                <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-800">
                    <div class="h-full w-4/5 rounded-full bg-teal-400"></div>
                </div>
                <p class="mt-2 text-xs text-slate-400">Marco atual: cadastro, origem e qualificação de Leads</p>
            </div>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <section class="metric-card">
            <p class="metric-label">Empresas</p>
            <p class="metric-value">{{ $stats['companies'] ?? '—' }}</p>
            <p class="metric-help">Organizações cadastradas no CRM</p>
        </section>
        <section class="metric-card">
            <p class="metric-label">Contatos ativos</p>
            <p class="metric-value">{{ $stats['active_contacts'] ?? '—' }}</p>
            <p class="metric-help">Pessoas disponíveis para atuação comercial</p>
        </section>
        <section class="metric-card">
            <p class="metric-label">Alta prioridade</p>
            <p class="metric-value">{{ $stats['high_priority_companies'] ?? '—' }}</p>
            <p class="metric-help">Empresas classificadas como alta ou crítica</p>
        </section>
        <section class="metric-card">
            <p class="metric-label">Decisores / champions</p>
            <p class="metric-value">{{ $stats['decision_contacts'] ?? '—' }}</p>
            <p class="metric-help">Contatos ativos com influência estratégica</p>
        </section>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1.35fr_.65fr]">
        <section class="card p-0">
            <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-950">Acesso rápido</h2>
                    <p class="mt-0.5 text-sm text-slate-500">Use o CRM enquanto os próximos módulos são implementados.</p>
                </div>
            </div>
            <div class="grid gap-3 p-5 sm:grid-cols-2 lg:grid-cols-3">
                @can('create', App\Models\Company::class)
                    <a href="{{ route('companies.create') }}" class="quick-action">
                        <span class="quick-action-icon">+</span>
                        <span><strong>Nova empresa</strong><small>Cadastrar organização</small></span>
                    </a>
                @endcan
                @can('create', App\Models\Contact::class)
                    <a href="{{ route('contacts.create') }}" class="quick-action">
                        <span class="quick-action-icon">+</span>
                        <span><strong>Novo contato</strong><small>Adicionar decisor ou influenciador</small></span>
                    </a>
                @endcan
                <a href="{{ route('leads.index') }}" class="quick-action">
                    <span class="quick-action-icon">→</span>
                    <span><strong>Leads</strong><small>Acessar módulo comercial</small></span>
                </a>
            </div>
        </section>

        <section class="card">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-teal-700">Roadmap</p>
                    <h2 class="mt-1 text-lg font-bold text-slate-950">Sprint 4 — Leads</h2>
                </div>
                <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-bold text-teal-700">Em andamento</span>
            </div>
            <ul class="mt-5 space-y-3 text-sm text-slate-700">
                <li class="roadmap-item"><span>01</span> Lead Sources e Channels</li>
                <li class="roadmap-item"><span>02</span> Cadastro e qualificação de Leads</li>
                <li class="roadmap-item"><span>03</span> First Touch / Last Touch</li>
                <li class="roadmap-item"><span>04</span> Filtros, follow-up e testes</li>
            </ul>
            <a href="{{ route('leads.index') }}" class="mt-5 inline-flex text-sm font-bold text-teal-700 hover:text-teal-900">Acessar Leads →</a>
        </section>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        @if($canViewCompanies)
            <section class="card p-0">
                <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-5 py-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-950">Empresas recentes</h2>
                        <p class="text-sm text-slate-500">Últimos cadastros realizados.</p>
                    </div>
                    <a href="{{ route('companies.index') }}" class="text-sm font-bold text-teal-700 hover:text-teal-900">Ver todas</a>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($recentCompanies as $company)
                        <a href="{{ route('companies.show', $company) }}" class="flex items-center justify-between gap-4 px-5 py-4 hover:bg-slate-50">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $company->trade_name ?: $company->legal_name }}</p>
                                <p class="mt-0.5 truncate text-xs text-slate-500">{{ collect([$company->city, $company->state])->filter()->join(' / ') ?: 'Localidade não informada' }}</p>
                            </div>
                            <span class="text-xs font-medium text-slate-400">{{ $company->created_at->format('d/m') }}</span>
                        </a>
                    @empty
                        <div class="empty-state">Nenhuma empresa cadastrada ainda.</div>
                    @endforelse
                </div>
            </section>
        @endif

        @if($canViewContacts)
            <section class="card p-0">
                <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-5 py-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-950">Contatos recentes</h2>
                        <p class="text-sm text-slate-500">Pessoas adicionadas mais recentemente.</p>
                    </div>
                    <a href="{{ route('contacts.index') }}" class="text-sm font-bold text-teal-700 hover:text-teal-900">Ver todos</a>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($recentContacts as $contact)
                        <a href="{{ route('contacts.show', $contact) }}" class="flex items-center justify-between gap-4 px-5 py-4 hover:bg-slate-50">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $contact->name }}</p>
                                <p class="mt-0.5 truncate text-xs text-slate-500">{{ $contact->company->trade_name ?: $contact->company->legal_name }}{{ $contact->job_title ? ' · '.$contact->job_title : '' }}</p>
                            </div>
                            <span class="text-xs font-medium {{ $contact->active ? 'text-emerald-600' : 'text-slate-400' }}">{{ $contact->active ? 'Ativo' : 'Inativo' }}</span>
                        </a>
                    @empty
                        <div class="empty-state">Nenhum contato cadastrado ainda.</div>
                    @endforelse
                </div>
            </section>
        @endif
    </div>
</x-layouts.app>
