@props(['title' => null, 'activeSprint' => null])
<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-crm-canvas">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Techsallus CRM' }} — Techsallus CRM</title>
    <script>
        try {
            if (localStorage.getItem('crm-sidebar-collapsed') === 'true') {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        } catch (error) {
            // O layout expandido continua sendo o fallback seguro.
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body @class([
    'min-h-full bg-crm-canvas text-slate-800 antialiased',
    'pipeline-view' => request()->routeIs('roadmap.pipeline'),
])>
    <div class="crm-sidebar-backdrop" data-sidebar-backdrop></div>

    <aside id="crm-sidebar" class="crm-sidebar" data-sidebar-panel aria-label="Navegação principal">
        <div class="sidebar-brand flex h-20 items-center justify-between border-b border-white/10 px-5">
            <a href="{{ route('dashboard') }}" class="group flex min-w-0 items-center gap-3">
                <span class="sidebar-brand-full flex h-11 w-36 shrink-0 items-center justify-center rounded-xl bg-white px-3 shadow-sm">
                    <img
                        src="{{ asset('images/techsallus-logo.png') }}"
                        alt="Techsallus"
                        class="w-full object-contain"
                    >
                </span>

                <span class="sidebar-brand-mark hidden h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-crm-blue text-xs font-black tracking-wide text-white shadow-sm">
                    TS
                </span>

                <span class="sidebar-brand-copy min-w-0">
                    <span class="block truncate text-sm font-bold tracking-tight text-crm-blue">
                        CRM Comercial
                    </span>
                    <span class="block text-[11px] text-slate-500">
                        Prospecção e relacionamento
                    </span>
                </span>
            </a>
            <button type="button" class="sidebar-collapse-button hidden rounded-lg p-2 text-slate-300 hover:bg-white/10 hover:text-white lg:inline-flex" data-sidebar-collapse aria-controls="crm-sidebar" aria-expanded="true" aria-label="Recolher menu" title="Recolher menu">
                <span aria-hidden="true" data-sidebar-collapse-icon>‹</span>
            </button>
            <button type="button" class="rounded-lg p-2 text-slate-400 hover:bg-slate-800 hover:text-white lg:hidden" data-sidebar-close aria-label="Fechar menu">✕</button>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-5" data-sidebar-nav>
            @can('dashboard.view')
                <p class="nav-section-title">Visão geral</p>
                <a href="{{ route('dashboard') }}" title="Dashboard" class="nav-link {{ request()->routeIs('dashboard') ? 'nav-link-active' : '' }}">
                    <span class="nav-mark">D</span>
                    <span>Dashboard</span>
                </a>
            @endcan

            <p class="nav-section-title mt-6">Comercial</p>
            @can('viewAny', App\Models\Company::class)
                <a href="{{ route('companies.index') }}" title="Empresas" class="nav-link {{ request()->routeIs('companies.*') ? 'nav-link-active' : '' }}">
                    <span class="nav-mark">E</span>
                    <span>Empresas</span>
                </a>
            @endcan
            @can('viewAny', App\Models\Contact::class)
                <a href="{{ route('contacts.index') }}" title="Contatos" class="nav-link {{ request()->routeIs('contacts.*') ? 'nav-link-active' : '' }}">
                    <span class="nav-mark">C</span>
                    <span>Contatos</span>
                </a>
            @endcan
            @can('viewAny', App\Models\Lead::class)
                <a href="{{ route('leads.index') }}" title="Leads" class="nav-link {{ request()->routeIs('leads.*') ? 'nav-link-active' : '' }}">
                    <span class="nav-mark">L</span>
                    <span class="flex-1">Leads</span>
                </a>
            @endcan
            @can('viewAny', App\Models\Opportunity::class)
                <a href="{{ route('roadmap.pipeline') }}" title="Pipeline" class="nav-link {{ request()->routeIs('roadmap.pipeline', 'opportunities.*') ? 'nav-link-active' : '' }}">
                    <span class="nav-mark">P</span>
                    <span class="flex-1">Pipeline</span>
                </a>
            @endcan

            <p class="nav-section-title mt-6">Operação</p>
            @can('viewAny', App\Models\Activity::class)
                <a href="{{ route('activities.index') }}" title="Atividades" class="nav-link {{ request()->routeIs('activities.*') ? 'nav-link-active' : '' }}">
                    <span class="nav-mark">A</span>
                    <span class="flex-1">Atividades</span>
                </a>
            @endcan
            @can('viewAny', App\Models\Task::class)
                <a href="{{ route('tasks.index') }}" title="Tarefas" class="nav-link {{ request()->routeIs('tasks.*') ? 'nav-link-active' : '' }}">
                    <span class="nav-mark">T</span>
                    <span class="flex-1">Tarefas</span>
                </a>
            @endcan
            @if(
                auth()->user()->hasPermission('activities.view')
                || auth()->user()->hasPermission('tasks.view')
            )
                <a
                    href="{{ route('timeline.index') }}"
                    title="Timeline"
                    class="nav-link {{ request()->routeIs('timeline.*') ? 'nav-link-active' : '' }}"
                >
                    <span class="nav-mark">TL</span>
                    <span class="flex-1">Timeline</span>
                </a>
            @endif

            @can('viewAny', App\Models\Campaign::class)
                <a href="{{ route('campaigns.index') }}" title="Campanhas" class="nav-link {{ request()->routeIs('campaigns.*') ? 'nav-link-active' : '' }}">
                    <span class="nav-mark">M</span>
                    <span class="flex-1">Campanhas</span>
                </a>
            @endcan

            <p class="nav-section-title mt-6">Inteligência</p>
            @can('viewAny', App\Models\DataImport::class)
                <a href="{{ route('imports.index') }}" title="Importações" class="nav-link {{ request()->routeIs('imports.*') ? 'nav-link-active' : '' }}">
                    <span class="nav-mark">I</span><span class="flex-1">Importações</span>
                </a>
            @endcan
            @can('reports.view')
                <a href="{{ route('reports.index') }}" title="Relatórios" class="nav-link {{ request()->routeIs('reports.*') ? 'nav-link-active' : '' }}">
                    <span class="nav-mark">R</span>
                    <span class="flex-1">Relatórios</span>
                </a>
            @endcan

            @if(auth()->user()->can('viewAny', App\Models\User::class) || auth()->user()->can('viewAny', App\Models\Role::class) || auth()->user()->can('viewAny', App\Models\Permission::class) || auth()->user()->can('viewAny', App\Models\AuditLog::class))
                <p class="nav-section-title mt-6">Administração</p>
                @can('viewAny', App\Models\User::class)
                    <a href="{{ route('admin.users.index') }}" title="Usuários" class="nav-link {{ request()->routeIs('admin.users.*') ? 'nav-link-active' : '' }}"><span class="nav-mark">U</span><span>Usuários</span></a>
                @endcan
                @can('viewAny', App\Models\Role::class)
                    <a href="{{ route('admin.roles.index') }}" title="Roles" class="nav-link {{ request()->routeIs('admin.roles.*') ? 'nav-link-active' : '' }}"><span class="nav-mark">R</span><span>Roles</span></a>
                @endcan
                @can('viewAny', App\Models\Permission::class)
                    <a href="{{ route('admin.permissions.index') }}" title="Permissões" class="nav-link {{ request()->routeIs('admin.permissions.*') ? 'nav-link-active' : '' }}"><span class="nav-mark">P</span><span>Permissões</span></a>
                @endcan
                @can('viewAny', App\Models\AuditLog::class)
                    <a href="{{ route('admin.audit.index') }}" title="Auditoria" class="nav-link {{ request()->routeIs('admin.audit.*') ? 'nav-link-active' : '' }}"><span class="nav-mark">A</span><span>Auditoria</span></a>
                @endcan
            @endif
        </nav>

        @if($activeSprint)
            <div class="border-t border-white/10 p-4" data-active-sprint>
                <div class="rounded-xl bg-slate-900 p-3">
                    <p class="text-xs font-semibold text-slate-300">Sprint atual</p>
                    <div class="mt-2 flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold text-crm-blue">{{ $activeSprint['title'] }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $activeSprint['description'] }}</p>
                        </div>
                        <span class="h-2.5 w-2.5 rounded-full bg-crm-sky shadow-[0_0_0_4px_rgba(92,166,214,0.14)]"></span>
                    </div>
                </div>
            </div>
        @endif
    </aside>

    <div class="crm-main min-h-screen">
        <header class="crm-topbar">
            <div class="flex min-w-0 items-center gap-3">
                <button type="button" class="rounded-lg border border-slate-200 bg-white p-2.5 text-slate-700 shadow-sm hover:bg-slate-50 lg:hidden" data-sidebar-toggle aria-label="Abrir menu">☰</button>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-slate-950">{{ $title ?? 'Techsallus CRM' }}</p>
                    <p class="hidden text-xs text-slate-500 sm:block">Gestão comercial, prospecção e relacionamento</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="hidden text-right sm:block">
                    <p class="text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-slate-500">{{ auth()->user()->primaryRole()?->name ?? 'Sem role' }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-crm-blue text-sm font-bold text-white shadow-sm">
                    {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn-secondary hidden sm:inline-flex" type="submit">Sair</button>
                    <button class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 sm:hidden" type="submit" aria-label="Sair">↪</button>
                </form>
            </div>
        </header>

        <main class="crm-content">
            @if(session('status'))
                <div class="alert-success font-medium" role="status">{{ session('status') }}</div>
            @endif
            {{ $slot }}
        </main>
    </div>
</body>
</html>
