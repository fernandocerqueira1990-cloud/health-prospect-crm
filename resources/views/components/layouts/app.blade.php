<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Health Prospect CRM' }} — Health Prospect CRM</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-slate-100 text-slate-800 antialiased">
    <div class="crm-sidebar-backdrop" data-sidebar-backdrop></div>

    <aside class="crm-sidebar" data-sidebar-panel aria-label="Navegação principal">
        <div class="flex h-20 items-center justify-between border-b border-slate-800 px-5">
            <a href="{{ route('dashboard') }}" class="group flex min-w-0 items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-500 font-black text-slate-950 shadow-lg shadow-teal-950/20">HP</span>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-bold tracking-tight text-white">Health Prospect</span>
                    <span class="block text-xs text-slate-400">CRM Comercial</span>
                </span>
            </a>
            <button type="button" class="rounded-lg p-2 text-slate-400 hover:bg-slate-800 hover:text-white lg:hidden" data-sidebar-close aria-label="Fechar menu">✕</button>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-5">
            @can('dashboard.view')
                <p class="nav-section-title">Visão geral</p>
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'nav-link-active' : '' }}">
                    <span class="nav-mark">D</span>
                    <span>Dashboard</span>
                </a>
            @endcan

            <p class="nav-section-title mt-6">Comercial</p>
            @can('viewAny', App\Models\Company::class)
                <a href="{{ route('companies.index') }}" class="nav-link {{ request()->routeIs('companies.*') ? 'nav-link-active' : '' }}">
                    <span class="nav-mark">E</span>
                    <span>Empresas</span>
                </a>
            @endcan
            @can('viewAny', App\Models\Contact::class)
                <a href="{{ route('contacts.index') }}" class="nav-link {{ request()->routeIs('contacts.*') ? 'nav-link-active' : '' }}">
                    <span class="nav-mark">C</span>
                    <span>Contatos</span>
                </a>
            @endcan
            @can('viewAny', App\Models\Lead::class)
                <a href="{{ route('leads.index') }}" class="nav-link {{ request()->routeIs('leads.*') ? 'nav-link-active' : '' }}">
                    <span class="nav-mark">L</span>
                    <span class="flex-1">Leads</span>
                    <span class="nav-badge">Sprint 4</span>
                </a>
            @endcan
            @can('viewAny', App\Models\Opportunity::class)
                <a href="{{ route('roadmap.pipeline') }}" class="nav-link {{ request()->routeIs('roadmap.pipeline', 'opportunities.*') ? 'nav-link-active' : '' }}">
                    <span class="nav-mark">P</span>
                    <span class="flex-1">Pipeline</span>
                    <span class="nav-badge">Sprint 5</span>
                </a>
            @endcan

            <p class="nav-section-title mt-6">Operação</p>
            @can('viewAny', App\Models\Activity::class)
                <a href="{{ route('activities.index') }}" class="nav-link {{ request()->routeIs('activities.*') ? 'nav-link-active' : '' }}">
                    <span class="nav-mark">A</span>
                    <span class="flex-1">Atividades</span>
                    <span class="nav-badge">Sprint 6</span>
                </a>
            @endcan
            @can('viewAny', App\Models\Task::class)
                <a href="{{ route('tasks.index') }}" class="nav-link {{ request()->routeIs('tasks.*') ? 'nav-link-active' : '' }}">
                    <span class="nav-mark">T</span>
                    <span class="flex-1">Tarefas</span>
                    <span class="nav-badge">Sprint 6</span>
                </a>
            @endcan
            @if(
                auth()->user()->hasPermission('activities.view')
                || auth()->user()->hasPermission('tasks.view')
            )
                <a
                    href="{{ route('timeline.index') }}"
                    class="nav-link {{ request()->routeIs('timeline.*') ? 'nav-link-active' : '' }}"
                >
                    <span class="nav-mark">TL</span>
                    <span class="flex-1">Timeline</span>
                    <span class="nav-badge">Sprint 6</span>
                </a>
            @endif

            <a href="{{ route('roadmap.campaigns') }}" class="nav-link {{ request()->routeIs('roadmap.campaigns') ? 'nav-link-active' : '' }}">
                <span class="nav-mark">M</span>
                <span class="flex-1">Campanhas</span>
                <span class="nav-badge-muted">Em breve</span>
            </a>

            <p class="nav-section-title mt-6">Inteligência</p>
            <a href="{{ route('roadmap.reports') }}" class="nav-link {{ request()->routeIs('roadmap.reports') ? 'nav-link-active' : '' }}">
                <span class="nav-mark">R</span>
                <span class="flex-1">Relatórios</span>
                <span class="nav-badge-muted">Em breve</span>
            </a>

            @if(auth()->user()->can('viewAny', App\Models\User::class) || auth()->user()->can('viewAny', App\Models\Role::class) || auth()->user()->can('viewAny', App\Models\Permission::class) || auth()->user()->can('viewAny', App\Models\AuditLog::class))
                <p class="nav-section-title mt-6">Administração</p>
                @can('viewAny', App\Models\User::class)
                    <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'nav-link-active' : '' }}"><span class="nav-mark">U</span><span>Usuários</span></a>
                @endcan
                @can('viewAny', App\Models\Role::class)
                    <a href="{{ route('admin.roles.index') }}" class="nav-link {{ request()->routeIs('admin.roles.*') ? 'nav-link-active' : '' }}"><span class="nav-mark">R</span><span>Roles</span></a>
                @endcan
                @can('viewAny', App\Models\Permission::class)
                    <a href="{{ route('admin.permissions.index') }}" class="nav-link {{ request()->routeIs('admin.permissions.*') ? 'nav-link-active' : '' }}"><span class="nav-mark">P</span><span>Permissões</span></a>
                @endcan
                @can('viewAny', App\Models\AuditLog::class)
                    <a href="{{ route('admin.audit.index') }}" class="nav-link {{ request()->routeIs('admin.audit.*') ? 'nav-link-active' : '' }}"><span class="nav-mark">A</span><span>Auditoria</span></a>
                @endcan
            @endif
        </nav>

        <div class="border-t border-slate-800 p-4">
            <div class="rounded-xl bg-slate-900 p-3">
                <p class="text-xs font-semibold text-slate-300">Sprint atual</p>
                <div class="mt-2 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-bold text-white">Sprint 6 — Atividades</p>
                        <p class="mt-0.5 text-xs text-slate-500">Módulo em desenvolvimento</p>
                    </div>
                    <span class="h-2.5 w-2.5 rounded-full bg-teal-400 shadow-[0_0_0_4px_rgba(45,212,191,0.10)]"></span>
                </div>
            </div>
        </div>
    </aside>

    <div class="min-h-screen lg:pl-72">
        <header class="sticky top-0 z-30 flex h-20 items-center justify-between border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6 lg:px-8">
            <div class="flex min-w-0 items-center gap-3">
                <button type="button" class="rounded-lg border border-slate-200 bg-white p-2.5 text-slate-700 shadow-sm hover:bg-slate-50 lg:hidden" data-sidebar-toggle aria-label="Abrir menu">☰</button>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-slate-950">{{ $title ?? 'Health Prospect CRM' }}</p>
                    <p class="hidden text-xs text-slate-500 sm:block">Gestão comercial, prospecção e relacionamento</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="hidden text-right sm:block">
                    <p class="text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-slate-500">{{ auth()->user()->primaryRole()?->name ?? 'Sem role' }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-900 text-sm font-bold text-white">
                    {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn-secondary hidden sm:inline-flex" type="submit">Sair</button>
                    <button class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 sm:hidden" type="submit" aria-label="Sair">↪</button>
                </form>
            </div>
        </header>

        <main class="mx-auto w-full max-w-[1600px] p-4 sm:p-6 lg:p-8">
            @if(session('status'))
                <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">{{ session('status') }}</div>
            @endif
            {{ $slot }}
        </main>
    </div>
</body>
</html>
