<x-layouts.app title="Deduplicação">
    <x-page-header title="Deduplicação" description="Revise candidatos e registre a intenção para a etapa final, sem alterar o CRM.">
        <x-slot:actions><a class="btn-secondary" href="{{ route('imports.preview', $dataImport) }}">Voltar ao Preview</a></x-slot:actions>
    </x-page-header>

    <x-errors />
    <div class="card mb-6">
        <dl class="grid gap-4 text-sm sm:grid-cols-3">
            <div><dt class="font-medium text-slate-500">Arquivo</dt><dd class="mt-1 font-semibold">{{ $dataImport->original_filename }}</dd></div>
            <div><dt class="font-medium text-slate-500">Tipo</dt><dd class="mt-1">{{ strtoupper($dataImport->type) }}</dd></div>
            <div><dt class="font-medium text-slate-500">Analisado em</dt><dd class="mt-1">{{ $analyzed_at ? Illuminate\Support\Carbon::parse($analyzed_at)->format('d/m/Y H:i') : 'Ainda não analisado' }}</dd></div>
        </dl>
        @can('update', $dataImport)
            <form class="mt-5" method="POST" action="{{ route('imports.dedup.analyze', $dataImport) }}">@csrf<button class="btn-primary" type="submit">{{ $analyzed_at ? 'Reanalisar duplicidades' : 'Analisar duplicidades' }}</button></form>
        @endcan
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['Total', $summary['total'] ?? 0, 'text-slate-900'], ['Sem duplicidade', $summary['clear'] ?? 0, 'text-emerald-700'],
            ['Em revisão', $summary['review'] ?? 0, 'text-amber-700'], ['Possíveis', $summary['possible_matches'] ?? 0, 'text-amber-700'], ['Fortes', $summary['exact_matches'] ?? 0, 'text-red-700'],
            ['Bloqueados', $summary['blocked'] ?? 0, 'text-red-700'], ['Resolvidos', $summary['resolved'] ?? 0, 'text-teal-700'],
        ] as [$label, $value, $class])
            <div class="card"><p class="text-sm text-slate-500">{{ $label }}</p><p class="mt-2 text-3xl font-bold {{ $class }}">{{ $value }}</p></div>
        @endforeach
    </div>

    <div class="space-y-5">
        @forelse($rows as $row)
            @php($dedup = $row->dedup_data)
            <article class="card">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <h2 class="font-bold text-slate-900">Linha {{ $row->row_number }}</h2>
                    <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ ['clear' => 'bg-emerald-100 text-emerald-800', 'review' => 'bg-amber-100 text-amber-800', 'resolved' => 'bg-teal-100 text-teal-800', 'blocked' => 'bg-red-100 text-red-800'][$dedup['status']] }}">{{ ['clear' => 'Sem duplicidade', 'review' => 'Revisão', 'resolved' => 'Resolvido', 'blocked' => 'Bloqueado'][$dedup['status']] }}</span>
                </div>
                <div class="grid gap-4 lg:grid-cols-3">
                    @foreach($dedup['groups'] as $group => $groupData)
                        <section class="rounded-xl border border-slate-200 p-4">
                            <h3 class="font-bold text-slate-900">{{ ['company' => 'Empresa', 'contact' => 'Contato', 'lead' => 'Lead'][$group] }}</h3>
                            <p class="mt-1 text-sm text-slate-500">Match: {{ ['none' => 'nenhum', 'possible' => 'possível', 'exact' => 'forte/exato'][$groupData['match']] }}</p>
                            <div class="mt-3 space-y-3">
                                @forelse($groupData['candidates'] as $candidate)
                                    <div class="rounded-lg bg-slate-50 p-3 text-sm">
                                        <p class="font-semibold">{{ $candidate['source'] === 'import' ? 'Linha '.$candidate['row_number'].' desta importação' : 'Registro do CRM' }} · {{ $candidate['strength'] === 'exact' ? 'forte' : 'possível' }}</p>
                                        @if($candidate['source'] === 'crm')
                                            @if($candidate['display']['restricted'] ?? true)
                                                <p class="mt-1 text-slate-600">Existe um registro correspondente no CRM, mas você não possui permissão para visualizar seus detalhes.</p>
                                            @else
                                                <p class="mt-1">{{ $candidate['display']['name'] ?? 'Sem nome' }}</p>
                                                @foreach(['tax_id', 'location', 'job_title', 'email', 'company', 'phone', 'status'] as $field)@if(!empty($candidate['display'][$field]))<p class="text-slate-500">{{ $candidate['display'][$field] }}</p>@endif @endforeach
                                                @if($candidate['display']['archived'])<p class="font-semibold text-amber-700">Arquivado</p>@endif
                                            @endif
                                        @endif
                                        <p class="mt-1 text-xs text-slate-500">Evidências: {{ implode(', ', $candidate['reasons']) }}</p>
                                    </div>
                                @empty
                                    <p class="text-sm text-emerald-700">Nenhum candidato encontrado.</p>
                                @endforelse
                            </div>
                            <p class="mt-3 text-sm">Decisão atual: <strong>{{ $groupData['decision']['action'] }}</strong></p>
                            @can('update', $dataImport)
                                @if($dedup['status'] !== 'blocked')
                                    @php($fiscalCreateBlocked = $group === 'company' && collect($groupData['candidates'])->contains(fn ($candidate) => $candidate['strength'] === 'exact' && in_array('tax_id_country_tax_id', $candidate['reasons'], true)))
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @foreach(($fiscalCreateBlocked ? ['skip' => 'Ignorar'] : ['create_new' => 'Criar novo', 'skip' => 'Ignorar']) as $action => $label)
                                            <form method="POST" action="{{ route('imports.dedup.update', [$dataImport, $row]) }}">@csrf @method('PUT')<input type="hidden" name="group" value="{{ $group }}"><input type="hidden" name="action" value="{{ $action }}"><button class="btn-secondary" type="submit">{{ $label }}</button></form>
                                        @endforeach
                                        @foreach($groupData['candidates'] as $candidate)
                                            <form method="POST" action="{{ route('imports.dedup.update', [$dataImport, $row]) }}">
                                                @csrf @method('PUT')
                                                <input type="hidden" name="group" value="{{ $group }}"><input type="hidden" name="action" value="{{ $candidate['source'] === 'crm' ? 'use_existing' : 'reuse_import_row' }}"><input type="hidden" name="candidate_ref" value="{{ $candidate['decision_ref'] }}">
                                                <button class="btn-secondary" type="submit">{{ $candidate['source'] === 'crm' ? 'Usar registro correspondente' : 'Reutilizar linha '.$candidate['row_number'] }}</button>
                                            </form>
                                        @endforeach
                                    </div>
                                @endif
                            @endcan
                        </section>
                    @endforeach
                </div>
            </article>
        @empty
            <div class="card text-center text-slate-500">Execute a análise para identificar possíveis duplicidades.</div>
        @endforelse
    </div>
    <div class="mt-5">{{ $rows->links() }}</div>
    <div class="mt-6 flex items-center gap-3">
        @can('update', $dataImport)<a class="btn-primary" href="{{ route('imports.execute.confirm', $dataImport) }}">Continuar para execução</a>@endcan
    </div>
</x-layouts.app>
