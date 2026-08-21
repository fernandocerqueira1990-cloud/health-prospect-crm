<x-layouts.app title="Dashboard">
    <section class="mb-5 overflow-hidden rounded-2xl border border-crm-light bg-white shadow-sm" aria-labelledby="dashboard-title">
        <div class="grid gap-4 px-5 py-5 sm:px-6 lg:grid-cols-[1.45fr_.55fr] lg:items-center lg:py-6">
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-crm-blue">Central comercial</p>
                <h1 id="dashboard-title" class="mt-1.5 text-2xl font-bold tracking-tight text-crm-navy sm:text-3xl">
                    Olá, {{ str(auth()->user()->name)->before(' ') }}.
                </h1>
                <p class="mt-1.5 max-w-2xl text-sm leading-5 text-slate-600">Acompanhe os principais indicadores e acesse rapidamente sua operação comercial.</p>
            </div>

            <div class="rounded-xl border border-crm-light bg-crm-ice px-4 py-3">
                <div class="flex items-center gap-3">
                    <span class="h-3 w-3 shrink-0 rounded-full bg-crm-sky shadow-[0_0_0_4px_rgba(92,166,214,0.14)]" aria-hidden="true"></span>
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-wider text-crm-blue">CRM operacional</p>
                        <p class="mt-0.5 text-sm leading-5 text-slate-600">Empresas, contatos, leads e pipeline em um só ambiente.</p>
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

    @if($canViewTasks && $commercialQueue)
        <section class="card mt-5 p-0" aria-labelledby="commercial-queue-title">
            <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-4 py-3 sm:px-5">
                <div class="min-w-0">
                    <h2 id="commercial-queue-title" class="text-base font-bold text-slate-950">Minhas pendências comerciais</h2>
                    <p class="mt-0.5 text-sm text-slate-500">Priorize as próximas ações e acompanhe tarefas com prazo.</p>
                </div>
                <a href="{{ route('tasks.index', ['assigned_user_id' => auth()->id()]) }}" class="shrink-0 text-sm font-bold text-crm-blue hover:text-crm-blue-dark">Ver tarefas</a>
            </div>

            <div class="grid gap-3 border-b border-slate-200 p-4 sm:grid-cols-3 sm:p-5">
                <x-metric-card
                    label="Atrasadas"
                    :value="$commercialQueue['overdue']"
                    help="Pendências anteriores a hoje"
                />
                <x-metric-card
                    label="Para hoje"
                    :value="$commercialQueue['today']"
                    help="Ações com vencimento hoje"
                />
                <x-metric-card
                    label="Próximas"
                    :value="$commercialQueue['upcoming']"
                    help="Ações agendadas após hoje"
                />
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($commercialQueue['next_tasks'] as $task)
                    <a href="{{ route('tasks.show', $task) }}" class="flex items-center justify-between gap-4 px-4 py-3 transition hover:bg-slate-50 sm:px-5">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $task->title }}</p>
                                @if($task->is_follow_up)
                                    <span class="rounded-full bg-crm-ice px-2 py-0.5 text-[11px] font-bold text-crm-blue">Follow-up</span>
                                @endif
                            </div>
                            <p class="mt-0.5 truncate text-xs text-slate-500">
                                {{ $task->lead?->name ?: $task->lead?->company_name ?: $task->opportunity?->title ?: 'Tarefa comercial' }}
                            </p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="text-xs font-bold {{ $task->due_at->isPast() ? 'text-rose-600' : ($task->due_at->isToday() ? 'text-amber-600' : 'text-slate-500') }}">
                                {{ $task->due_at->format('d/m H:i') }}
                            </p>
                            <p class="mt-0.5 text-[11px] text-slate-400">{{ $task->status === 'in_progress' ? 'Em andamento' : 'Pendente' }}</p>
                        </div>
                    </a>
                @empty
                    <x-empty-state title="Nenhuma pendência comercial com prazo." class="py-6" />
                @endforelse
            </div>
        </section>
    @endif

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
                    <a href="{{ route('companies.index') }}" class="shrink-0 text-sm font-bold text-crm-blue hover:text-crm-blue-dark">Ver todas</a>
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
                    <a href="{{ route('contacts.index') }}" class="shrink-0 text-sm font-bold text-crm-blue hover:text-crm-blue-dark">Ver todos</a>
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
