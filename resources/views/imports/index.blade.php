<x-layouts.app title="Importações">
    <x-page-header title="Importações" description="Arquivos recebidos e interpretados pelo CRM.">
        <x-slot:actions>
            @can('create', App\Models\DataImport::class)
                <a class="btn-primary" href="{{ route('imports.create') }}">Nova importação</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Arquivo</th><th>Tipo</th><th>Status</th><th>Linhas</th><th>Usuário</th><th>Data</th><th></th></tr></thead>
            <tbody>
                @forelse($imports as $dataImport)
                    <tr>
                        <td><a class="font-semibold text-teal-700 hover:text-teal-900" href="{{ route('imports.show', $dataImport) }}">{{ $dataImport->original_filename }}</a></td>
                        <td>{{ strtoupper($dataImport->type) }}</td>
                        <td>{{ ['uploaded' => 'Enviado', 'processing' => 'Processando', 'parsed' => 'Interpretado', 'failed' => 'Falhou'][$dataImport->status] ?? $dataImport->status }}</td>
                        <td>{{ $dataImport->total_rows }}</td>
                        <td>{{ $dataImport->user->name }}</td>
                        <td>{{ $dataImport->created_at->format('d/m/Y H:i') }}</td>
                        <td class="text-right"><a class="text-sm font-semibold text-teal-700" href="{{ route('imports.show', $dataImport) }}">Ver resultado</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-10 text-center text-slate-500">Nenhuma importação encontrada.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $imports->links() }}</div>
</x-layouts.app>
