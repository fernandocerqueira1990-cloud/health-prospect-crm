<x-layouts.app :title="$module">
    <x-page-header :title="$module" :description="$description" />

    <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
        <section class="card">
            <div class="flex flex-wrap items-center gap-3">
                <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-bold text-teal-700">{{ $sprint }}</span>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Planejado</span>
            </div>
            <h2 class="mt-5 text-2xl font-bold tracking-tight text-slate-950">Este módulo já está previsto na arquitetura.</h2>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">A navegação foi liberada antecipadamente para você validar a experiência do CRM no navegador. A implementação funcional será conectada a esta mesma área durante a sprint correspondente.</p>

            <div class="mt-6 grid gap-3 sm:grid-cols-2">
                @foreach($items as $index => $item)
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <span class="text-xs font-bold text-teal-700">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $item }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <aside class="card h-fit">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Status do projeto</p>
            <div class="mt-4 space-y-4">
                <div class="flex items-start gap-3">
                    <span class="mt-1 h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                    <div><p class="text-sm font-semibold text-slate-900">Sprints 0–3</p><p class="text-xs text-slate-500">Core, identidade, empresas e contatos concluídos.</p></div>
                </div>
                <div class="flex items-start gap-3">
                    <span class="mt-1 h-2.5 w-2.5 rounded-full bg-teal-500"></span>
                    <div><p class="text-sm font-semibold text-slate-900">Sprint 3.5</p><p class="text-xs text-slate-500">Front-base e validação visual.</p></div>
                </div>
                <div class="flex items-start gap-3">
                    <span class="mt-1 h-2.5 w-2.5 rounded-full bg-slate-300"></span>
                    <div><p class="text-sm font-semibold text-slate-900">{{ $sprint }}</p><p class="text-xs text-slate-500">Implementação funcional futura.</p></div>
                </div>
            </div>
            <a href="{{ route('dashboard') }}" class="btn-secondary mt-6 w-full">Voltar ao Dashboard</a>
        </aside>
    </div>
</x-layouts.app>
