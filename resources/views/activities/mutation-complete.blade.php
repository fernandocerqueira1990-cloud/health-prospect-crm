<x-layouts.app title="Operação concluída">
    <div class="card mx-auto max-w-2xl text-center">
        <h1 class="text-xl font-bold text-slate-950">
            Operação concluída
        </h1>

        <p class="mt-2 text-sm text-slate-500">
            A alteração foi realizada com sucesso.
        </p>

        <div class="mt-6">
            <a class="btn-primary" href="{{ route('dashboard') }}">
                Voltar ao dashboard
            </a>
        </div>
    </div>
</x-layouts.app>
