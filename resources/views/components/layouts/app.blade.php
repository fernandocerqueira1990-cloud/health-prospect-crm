<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Health Prospect CRM' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full text-slate-800 antialiased">
<div class="min-h-full lg:flex">
    <aside class="bg-slate-950 text-slate-200 lg:fixed lg:inset-y-0 lg:w-64">
        <div class="flex h-16 items-center justify-between border-b border-slate-800 px-5">
            <a href="{{ route('dashboard') }}" class="font-semibold tracking-tight text-white">Health Prospect CRM</a>
            <button type="button" class="rounded p-2 lg:hidden" data-sidebar-toggle aria-label="Alternar menu">☰</button>
        </div>
        <nav class="hidden space-y-1 p-4 lg:block" data-sidebar>
            @can('dashboard.view')
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'nav-link-active' : '' }}">Dashboard</a>
            @endcan

            @if(auth()->user()->can('viewAny', App\Models\User::class) || auth()->user()->can('viewAny', App\Models\Role::class) || auth()->user()->can('viewAny', App\Models\Permission::class) || auth()->user()->can('viewAny', App\Models\AuditLog::class))
                <p class="px-3 pb-1 pt-6 text-xs font-semibold uppercase tracking-wider text-slate-500">Administração</p>
                @can('viewAny', App\Models\User::class)<a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'nav-link-active' : '' }}">Usuários</a>@endcan
                @can('viewAny', App\Models\Role::class)<a href="{{ route('admin.roles.index') }}" class="nav-link {{ request()->routeIs('admin.roles.*') ? 'nav-link-active' : '' }}">Roles</a>@endcan
                @can('viewAny', App\Models\Permission::class)<a href="{{ route('admin.permissions.index') }}" class="nav-link {{ request()->routeIs('admin.permissions.*') ? 'nav-link-active' : '' }}">Permissões</a>@endcan
                @can('viewAny', App\Models\AuditLog::class)<a href="{{ route('admin.audit.index') }}" class="nav-link {{ request()->routeIs('admin.audit.*') ? 'nav-link-active' : '' }}">Auditoria</a>@endcan
            @endif

            @can('viewAny', App\Models\Company::class)
                <p class="px-3 pb-1 pt-6 text-xs font-semibold uppercase tracking-wider text-slate-500">Comercial</p>
                <a href="{{ route('companies.index') }}" class="nav-link {{ request()->routeIs('companies.*') ? 'nav-link-active' : '' }}">Empresas</a>
            @endcan
        </nav>
    </aside>

    <div class="min-w-0 flex-1 lg:pl-64">
        <header class="flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 shadow-sm sm:px-6">
            <div>
                <p class="font-medium text-slate-900">{{ auth()->user()->name }}</p>
                <p class="text-xs text-slate-500">{{ auth()->user()->primaryRole()?->name ?? 'Sem role' }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn-secondary" type="submit">Sair</button>
            </form>
        </header>
        <main class="p-4 sm:p-6 lg:p-8">
            @if(session('status'))<div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('status') }}</div>@endif
            {{ $slot }}
        </main>
    </div>
</div>
</body>
</html>
