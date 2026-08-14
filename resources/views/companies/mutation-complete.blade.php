<x-layouts.app title="Operação concluída">
    <div class="mx-auto max-w-xl">
        <section class="card text-center">
            <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-emerald-100 text-2xl text-emerald-700" aria-hidden="true">✓</div>
            <h1 class="mt-4 text-xl font-semibold text-slate-900">Operação concluída</h1>
            <p class="mt-2 text-sm text-slate-600">A alteração foi processada com sucesso.</p>
            @can('dashboard.view')
                <a class="btn-secondary mt-6" href="{{ route('dashboard') }}">Ir para o dashboard</a>
            @endcan
        </section>
    </div>
</x-layouts.app>
