@props(['title' => null, 'activeSprint' => null])
@php
    $unreadNotificationsCount = auth()->user()->unreadNotifications()->count();
@endphp
<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-[#061a30]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'CRM X' }} — CRM X</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body @class([
    'crm-app-body min-h-full antialiased',
    'pipeline-view' => request()->routeIs('roadmap.pipeline'),
])>
    <div class="crm-sidebar-backdrop" data-sidebar-backdrop></div>

    <aside id="crm-sidebar" class="crm-sidebar" data-sidebar-panel aria-label="Navegação principal">
        <div class="sidebar-brand flex h-20 items-center justify-between border-b border-white/10 px-5">
            <a href="{{ route('dashboard') }}" class="group flex min-w-0 items-center gap-3">

                <span class="sidebar-brand-full flex h-11 shrink-0 items-center">
    <img
        src="{{ asset('images/techsallus-logo-transparent.png') }}"
        alt="Techsallus"
        class="crm-sidebar-logo"
    >
</span>

                <span class="sidebar-brand-mark hidden h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-crm-blue text-xs font-black tracking-wide text-white shadow-sm">X</span>
                <span class="sidebar-brand-copy min-w-0"><span class="block truncate text-sm font-bold tracking-tight text-crm-blue">CRM X</span><span class="block text-[11px] text-slate-500">Prospecção e relacionamento</span></span>
            </a>
            <button type="button" class="sidebar-collapse-button hidden rounded-lg p-2 text-slate-300 hover:bg-white/10 hover:text-white lg:inline-flex" data-sidebar-collapse aria-controls="crm-sidebar" aria-expanded="true" aria-label="Recolher menu" title="Recolher menu"><span aria-hidden="true" data-sidebar-collapse-icon>‹</span></button>
            <button type="button" class="rounded-lg p-2 text-slate-400 hover:bg-slate-800 hover:text-white lg:hidden" data-sidebar-close aria-label="Fechar menu">✕</button>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-5" data-sidebar-nav>
            @can('dashboard.view')
                <p class="nav-section-title">Visão geral</p>
                <a href="{{ route('dashboard') }}" title="Dashboard" class="nav-link {{ request()->routeIs('dashboard') ? 'nav-link-active' : '' }}"><span class="nav-mark">D</span><span>Dashboard</span></a>
            @endcan

            <p class="nav-section-title mt-6">Comercial</p>
            @can('viewAny', App\Models\Company::class)<a href="{{ route('companies.index') }}" title="Empresas" class="nav-link {{ request()->routeIs('companies.*') ? 'nav-link-active' : '' }}"><span class="nav-mark">E</span><span>Empresas</span></a>@endcan
            @can('viewAny', App\Models\Contact::class)<a href="{{ route('contacts.index') }}" title="Contatos" class="nav-link {{ request()->routeIs('contacts.*') ? 'nav-link-active' : '' }}"><span class="nav-mark">C</span><span>Contatos</span></a>@endcan
            @can('viewAny', App\Models\Lead::class)<a href="{{ route('leads.index') }}" title="Leads" class="nav-link {{ request()->routeIs('leads.*') ? 'nav-link-active' : '' }}"><span class="nav-mark">L</span><span class="flex-1">Leads</span></a>@endcan
            @can('viewAny', App\Models\Opportunity::class)<a href="{{ route('roadmap.pipeline') }}" title="Pipeline" class="nav-link {{ request()->routeIs('roadmap.pipeline', 'opportunities.*') ? 'nav-link-active' : '' }}"><span class="nav-mark">P</span><span class="flex-1">Pipeline</span></a>@endcan

            <p class="nav-section-title mt-6">Operação</p>
            @can('viewAny', App\Models\Activity::class)<a href="{{ route('activities.index') }}" title="Atividades" class="nav-link {{ request()->routeIs('activities.*') ? 'nav-link-active' : '' }}"><span class="nav-mark">A</span><span class="flex-1">Atividades</span></a>@endcan
            @can('viewAny', App\Models\Task::class)<a href="{{ route('tasks.index') }}" title="Tarefas" class="nav-link {{ request()->routeIs('tasks.*') ? 'nav-link-active' : '' }}"><span class="nav-mark">T</span><span class="flex-1">Tarefas</span></a>@endcan
            @if(auth()->user()->hasPermission('activities.view') || auth()->user()->hasPermission('tasks.view'))
                <a href="{{ route('timeline.index') }}" title="Timeline" class="nav-link {{ request()->routeIs('timeline.*') ? 'nav-link-active' : '' }}"><span class="nav-mark">TL</span><span class="flex-1">Timeline</span></a>
            @endif
            <a href="{{ route('notifications.index') }}" title="Notificações" class="nav-link {{ request()->routeIs('notifications.*') ? 'nav-link-active' : '' }}"><span class="nav-mark">N</span><span class="flex-1">Notificações</span>@if($unreadNotificationsCount > 0)<span class="rounded-full bg-rose-500 px-2 py-0.5 text-[10px] font-extrabold text-white">{{ $unreadNotificationsCount > 99 ? '99+' : $unreadNotificationsCount }}</span>@endif</a>
            @can('viewAny', App\Models\Campaign::class)<a href="{{ route('campaigns.index') }}" title="Campanhas" class="nav-link {{ request()->routeIs('campaigns.*') ? 'nav-link-active' : '' }}"><span class="nav-mark">M</span><span class="flex-1">Campanhas</span></a>@endcan

            <p class="nav-section-title mt-6">Inteligência</p>
            @can('viewAny', App\Models\DataImport::class)<a href="{{ route('imports.index') }}" title="Importações" class="nav-link {{ request()->routeIs('imports.*') ? 'nav-link-active' : '' }}"><span class="nav-mark">I</span><span class="flex-1">Importações</span></a>@endcan
            @can('reports.view')<a href="{{ route('reports.index') }}" title="Relatórios" class="nav-link {{ request()->routeIs('reports.*') ? 'nav-link-active' : '' }}"><span class="nav-mark">R</span><span class="flex-1">Relatórios</span></a>@endcan

            @if(auth()->user()->can('viewAny', App\Models\User::class) || auth()->user()->can('viewAny', App\Models\Role::class) || auth()->user()->can('viewAny', App\Models\Permission::class) || auth()->user()->can('viewAny', App\Models\AuditLog::class))
                <p class="nav-section-title mt-6">Administração</p>
                @can('viewAny', App\Models\User::class)<a href="{{ route('admin.users.index') }}" title="Usuários" class="nav-link {{ request()->routeIs('admin.users.*') ? 'nav-link-active' : '' }}"><span class="nav-mark">U</span><span>Usuários</span></a>@endcan
                @can('viewAny', App\Models\Role::class)<a href="{{ route('admin.roles.index') }}" title="Roles" class="nav-link {{ request()->routeIs('admin.roles.*') ? 'nav-link-active' : '' }}"><span class="nav-mark">R</span><span>Roles</span></a>@endcan
                @can('viewAny', App\Models\Permission::class)<a href="{{ route('admin.permissions.index') }}" title="Permissões" class="nav-link {{ request()->routeIs('admin.permissions.*') ? 'nav-link-active' : '' }}"><span class="nav-mark">P</span><span>Permissões</span></a>@endcan
                @can('viewAny', App\Models\AuditLog::class)<a href="{{ route('admin.audit.index') }}" title="Auditoria" class="nav-link {{ request()->routeIs('admin.audit.*') ? 'nav-link-active' : '' }}"><span class="nav-mark">A</span><span>Auditoria</span></a>@endcan
            @endif
        </nav>

        @if($activeSprint)
            <div class="border-t border-white/10 p-4" data-active-sprint><div class="rounded-xl bg-slate-900 p-3"><p class="text-xs font-semibold text-slate-300">Sprint atual</p><div class="mt-2 flex items-center justify-between gap-3"><div><p class="text-sm font-bold text-crm-blue">{{ $activeSprint['title'] }}</p><p class="mt-0.5 text-xs text-slate-500">{{ $activeSprint['description'] }}</p></div><span class="h-2.5 w-2.5 rounded-full bg-crm-sky shadow-[0_0_0_4px_rgba(92,166,214,0.14)]"></span></div></div></div>
        @endif
    </aside>

    <div class="crm-main min-h-screen">
        <header class="crm-topbar">
            <div class="flex min-w-0 items-center gap-3"><button type="button" class="crm-topbar-mobile-toggle lg:hidden" data-sidebar-toggle aria-label="Abrir menu">☰</button><div class="min-w-0"><p class="truncate text-sm font-semibold text-slate-950">{{ $title ?? 'CRM X' }}</p><p class="hidden text-xs text-slate-500 sm:block">Gestão comercial, prospecção e relacionamento</p></div></div>
            <div class="flex items-center gap-3">
                <a href="{{ route('notifications.index') }}" class="crm-topbar-notification" aria-label="Notificações{{ $unreadNotificationsCount > 0 ? ': '.$unreadNotificationsCount.' não lidas' : '' }}" title="Notificações"><span aria-hidden="true" class="flex items-center justify-center">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17H18a2 2 0 0 0 2-2v-1.586a1 1 0 0 0-.293-.707l-1.414-1.414A2 2 0 0 1 17.707 10V8a5.707 5.707 0 1 0-11.414 0v2a2 2 0 0 1-.586 1.293L4.293 12.707A1 1 0 0 0 4 13.414V15a2 2 0 0 0 2 2h3.143m5.714 0a3 3 0 1 1-5.714 0m5.714 0H9.143"/>
    </svg>
</span>@if($unreadNotificationsCount > 0)<span class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-extrabold text-white">{{ $unreadNotificationsCount > 9 ? '9+' : $unreadNotificationsCount }}</span>@endif</a>
                <div class="hidden text-right sm:block"><p class="text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p><p class="text-xs text-slate-500">{{ auth()->user()->primaryRole()?->name ?? 'Sem role' }}</p></div>
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-crm-blue text-sm font-bold text-white shadow-sm">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</div>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn-secondary hidden sm:inline-flex" type="submit">Sair</button><button class="crm-topbar-exit sm:hidden" type="submit" aria-label="Sair">↪</button></form>
            </div>
        </header>

        <main class="crm-content">
            @if(session('status'))<div class="alert-success font-medium" role="status">{{ session('status') }}</div>@endif
            {{ $slot }}
        </main>
    </div>
</body>
</html>
