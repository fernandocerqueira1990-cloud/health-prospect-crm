<x-layouts.app title="Dashboard">
    <x-page-header title="Dashboard" description="Visão inicial do ambiente administrativo." />
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        <section class="card"><p class="text-sm font-medium text-slate-500">Sessão</p><p class="mt-2 text-xl font-semibold text-emerald-700">Autenticada</p></section>
        <section class="card"><p class="text-sm font-medium text-slate-500">Seu acesso</p><p class="mt-2 text-xl font-semibold">{{ auth()->user()->primaryRole()?->name ?? 'Sem role' }}</p></section>
        <section class="card"><p class="text-sm font-medium text-slate-500">Último login</p><p class="mt-2 text-xl font-semibold">{{ auth()->user()->last_login_at?->format('d/m/Y H:i') ?? 'Primeiro acesso' }}</p></section>
    </div>
</x-layouts.app>
