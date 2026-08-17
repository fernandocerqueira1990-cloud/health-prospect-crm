<x-layouts.app title="Relatório da importação">
    <x-page-header title="Relatório da importação" description="Resultado final da execução por linha e por entidade.">
        <x-slot:actions><a class="btn-secondary" href="{{ route('imports.show', $dataImport) }}">Ver importação</a></x-slot:actions>
    </x-page-header>

    @php($rowSummary = $summary['rows'] ?? [])
    @php($entitySummary = $summary['entities'] ?? [])
    <div class="card mb-6"><dl class="grid gap-4 text-sm sm:grid-cols-3 lg:grid-cols-6">
        <x-import-filename label-class="text-slate-500">{{ $dataImport->original_filename }}</x-import-filename>
        <div><dt class="text-slate-500">Início</dt><dd>{{ $dataImport->started_at?->format('d/m/Y H:i:s') ?? '—' }}</dd></div>
        <div><dt class="text-slate-500">Término</dt><dd>{{ $dataImport->finished_at?->format('d/m/Y H:i:s') ?? '—' }}</dd></div>
        <div><dt class="text-slate-500">Duração</dt><dd>{{ $duration_seconds === null ? '—' : $duration_seconds.'s' }}</dd></div>
        <div><dt class="text-slate-500">Executor</dt><dd>{{ $executor_name ?? '—' }}</dd></div>
        <div><dt class="text-slate-500">Duplicados</dt><dd>{{ $dataImport->duplicate_rows }}</dd></div>
    </dl></div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
        @foreach([['Total', $dataImport->total_rows], ['Importadas', $dataImport->imported_rows], ['Reutilizadas', $rowSummary['reused'] ?? 0], ['Ignoradas', $rowSummary['skipped'] ?? 0], ['Bloqueadas', $rowSummary['blocked'] ?? 0], ['Falhas', $dataImport->failed_rows]] as [$label, $value])
            <div class="card"><p class="text-sm text-slate-500">{{ $label }}</p><p class="mt-2 text-3xl font-bold">{{ $value }}</p></div>
        @endforeach
    </div>

    <div class="card mb-6"><h2 class="font-bold">Entidades</h2><div class="mt-4 grid gap-4 sm:grid-cols-3">
        @foreach(['company' => 'Empresas', 'contact' => 'Contatos', 'lead' => 'Leads'] as $group => $label)
            <div><p class="font-semibold">{{ $label }}</p><p class="text-sm text-slate-600">Criadas: {{ $entitySummary[$group]['created'] ?? 0 }} · Reutilizadas: {{ $entitySummary[$group]['reused'] ?? 0 }} · Ignoradas: {{ $entitySummary[$group]['skipped'] ?? 0 }}</p></div>
        @endforeach
    </div></div>

    <form class="card mb-6 flex items-end gap-3" method="GET" action="{{ route('imports.report', $dataImport) }}"><div><label class="label" for="status">Resultado</label><select class="input" id="status" name="status">@foreach(['all' => 'Todos', 'success' => 'Criados', 'reused' => 'Reutilizados', 'skipped' => 'Ignorados', 'failed' => 'Falhas', 'blocked' => 'Bloqueados'] as $value => $label)<option value="{{ $value }}" @selected($filter === $value)>{{ $label }}</option>@endforeach</select></div><button class="btn-primary">Filtrar</button></form>

    <div class="table-wrap"><table class="table"><thead><tr><th>Linha</th><th>Status</th><th>Empresa</th><th>Contato</th><th>Lead</th><th>Erro</th></tr></thead><tbody>
        @forelse($rows as $row)
            @php($execution = $row->execution_data)
            <tr><td>{{ $row->row_number }}</td><td>{{ ['success' => 'Importada', 'reused' => 'Reutilizada', 'skipped' => 'Ignorada', 'failed' => 'Falhou', 'blocked' => 'Bloqueada'][$execution['status']] ?? 'Desconhecido' }}</td>
                @foreach(['company', 'contact', 'lead'] as $group)
                    @php($result = $execution['groups'][$group] ?? null)
                    <td>@if($result === null) — @else {{ ['created' => 'Criado', 'reused' => 'Reutilizado', 'skipped' => 'Ignorado'][$result['result']] ?? 'Falhou' }} @if(isset($result['entity_id'])) · {{ $can_view_ids[$group] ? '#'.$result['entity_id'] : 'ID protegido' }} @endif @endif</td>
                @endforeach
                <td>{{ ['strong_duplicate_changed' => 'A identidade fiscal passou a conflitar com uma empresa existente.', 'missing_company_dependency' => 'Não foi possível resolver uma empresa para o contato.', 'invalid_existing_candidate' => 'Um candidato selecionado não está mais disponível.', 'archived_existing_candidate' => 'Um candidato está arquivado.', 'lead_acquisition_changed' => 'A origem ou o canal deixou de estar disponível.', 'constraint_conflict' => 'Uma regra de integridade impediu a linha.', 'execution_failed' => 'A linha não pôde ser executada.'][$execution['error_code'] ?? ''] ?? '—' }}</td>
            </tr>
        @empty <tr><td colspan="6" class="py-10 text-center text-slate-500">Nenhuma linha corresponde ao filtro.</td></tr> @endforelse
    </tbody></table></div><div class="mt-5">{{ $rows->links() }}</div>
</x-layouts.app>
