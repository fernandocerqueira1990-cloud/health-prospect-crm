<x-layouts.app title="Resultado da importação">
    <x-page-header title="Resultado da importação" description="Confirmação da leitura inicial do arquivo.">
        <x-slot:actions><a class="btn-secondary" href="{{ route('imports.index') }}">Voltar</a></x-slot:actions>
    </x-page-header>
    <div class="card max-w-3xl">
        <dl class="grid gap-5 sm:grid-cols-2">
            <div><dt class="text-sm font-medium text-slate-500">Arquivo</dt><dd class="mt-1 font-semibold text-slate-900">{{ $dataImport->original_filename }}</dd></div>
            <div><dt class="text-sm font-medium text-slate-500">Tipo</dt><dd class="mt-1">{{ strtoupper($dataImport->type) }}</dd></div>
            <div><dt class="text-sm font-medium text-slate-500">Status</dt><dd class="mt-1">{{ ['uploaded' => 'Enviado', 'processing' => 'Processando', 'parsed' => 'Interpretado', 'failed' => 'Falhou'][$dataImport->status] ?? $dataImport->status }}</dd></div>
            <div><dt class="text-sm font-medium text-slate-500">Linhas interpretadas</dt><dd class="mt-1">{{ $dataImport->total_rows }}</dd></div>
            <div><dt class="text-sm font-medium text-slate-500">Usuário</dt><dd class="mt-1">{{ $dataImport->user->name }}</dd></div>
            <div><dt class="text-sm font-medium text-slate-500">Data</dt><dd class="mt-1">{{ $dataImport->created_at->format('d/m/Y H:i') }}</dd></div>
        </dl>
        @if($dataImport->status === App\Models\DataImport::STATUS_FAILED)
            <p class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">O arquivo não pôde ser interpretado. Verifique o formato, o cabeçalho e a consistência das colunas.</p>
        @endif
        @if($dataImport->status === App\Models\DataImport::STATUS_PARSED)
            @can('view', $dataImport)
                <div class="mt-6 border-t border-slate-200 pt-6">
                    <a class="btn-secondary" href="{{ route('imports.preview', $dataImport) }}">Visualizar Preview</a>
                </div>
            @endcan
            @can('update', $dataImport)
                <div class="mt-6 border-t border-slate-200 pt-6">
                    <a class="btn-primary" href="{{ route('imports.mapping.edit', $dataImport) }}">Mapear colunas</a>
                </div>
            @endcan
        @endif
        @can('delete', $dataImport)
            <form class="mt-6 border-t border-slate-200 pt-6" method="POST" action="{{ route('imports.destroy', $dataImport) }}" onsubmit="return confirm('Excluir esta importação e seu arquivo privado?')">
                @csrf @method('DELETE')
                <button class="btn-secondary" type="submit">Excluir importação</button>
            </form>
        @endcan
    </div>
</x-layouts.app>
