<x-layouts.app title="Nova importação">
    <x-page-header title="Nova importação" description="Envie um arquivo para interpretação inicial." />
    <div class="card max-w-2xl">
        <x-errors />
        <form method="POST" action="{{ route('imports.store') }}" enctype="multipart/form-data">
            @csrf
            <div>
                <label class="label" for="file">Arquivo CSV ou XLSX</label>
                <input class="input" id="file" name="file" type="file" accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                <p class="mt-2 text-sm text-slate-500">Formatos suportados: CSV e XLSX. Limite: {{ $maxUploadMb }} MB.</p>
            </div>
            <div class="mt-6 flex flex-wrap gap-3">
                <button class="btn-primary" type="submit">Importar</button>
                <a class="btn-secondary" href="{{ route('imports.index') }}">Cancelar</a>
            </div>
        </form>
    </div>
</x-layouts.app>
