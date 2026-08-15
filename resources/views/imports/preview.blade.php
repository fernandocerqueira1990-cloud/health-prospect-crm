<x-layouts.app title="Preview da importação">
    <x-page-header title="Preview da importação" description="Valide os dados normalizados antes de continuar o fluxo de importação.">
        <x-slot:actions>
            @can('update', $dataImport)
                <a class="btn-secondary" href="{{ route('imports.mapping.edit', $dataImport) }}">Voltar ao mapeamento</a>
            @endcan
            <a class="btn-secondary" href="{{ route('imports.show', $dataImport) }}">Ver importação</a>
        </x-slot:actions>
    </x-page-header>

    <div class="card mb-6">
        <dl class="grid gap-4 text-sm sm:grid-cols-4">
            <div><dt class="font-medium text-slate-500">Arquivo</dt><dd class="mt-1 font-semibold text-slate-900">{{ $dataImport->original_filename }}</dd></div>
            <div><dt class="font-medium text-slate-500">Tipo</dt><dd class="mt-1">{{ strtoupper($dataImport->type) }}</dd></div>
            <div><dt class="font-medium text-slate-500">Linhas</dt><dd class="mt-1">{{ $counts['total'] }}</dd></div>
            <div><dt class="font-medium text-slate-500">Campos mapeados</dt><dd class="mt-1">{{ $mappedCount }}</dd></div>
        </dl>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['label' => 'Total', 'value' => $counts['total'], 'class' => 'text-slate-900'],
            ['label' => 'Válidos', 'value' => $counts['valid'], 'class' => 'text-emerald-700'],
            ['label' => 'Avisos', 'value' => $counts['warning'], 'class' => 'text-amber-700'],
            ['label' => 'Erros', 'value' => $counts['error'], 'class' => 'text-red-700'],
        ] as $card)
            <div class="card"><p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p><p class="mt-2 text-3xl font-bold {{ $card['class'] }}">{{ $card['value'] }}</p></div>
        @endforeach
    </div>

    <form class="card mb-6 flex flex-wrap items-end gap-4" method="GET" action="{{ route('imports.preview', $dataImport) }}">
        <div>
            <label class="label" for="status">Classificação</label>
            <select class="input min-w-48" id="status" name="status">
                @foreach(['all' => 'Todos', 'valid' => 'Válidos', 'warning' => 'Com avisos', 'error' => 'Com erros'] as $value => $label)
                    <option value="{{ $value }}" @selected($filter === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label" for="per_page">Linhas por página</label>
            <select class="input min-w-32" id="per_page" name="per_page">
                @foreach([25, 50, 100] as $value)
                    <option value="{{ $value }}" @selected($perPage === $value)>{{ $value }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn-primary" type="submit">Aplicar</button>
    </form>

    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Linha</th><th>Empresa</th><th>Contato</th><th>Lead</th><th>Status</th><th>Problemas</th><th>Ação</th></tr></thead>
            <tbody>
                @forelse($rows as $row)
                    @php($company = $row['data']['company'] ?? [])
                    @php($contact = $row['data']['contact'] ?? [])
                    @php($lead = $row['data']['lead'] ?? [])
                    <tr>
                        <td class="font-semibold">{{ $row['row_number'] }}</td>
                        <td class="max-w-xs text-sm">
                            @if($company !== [])
                                <p class="font-semibold text-slate-900">{{ $company['trade_name'] ?? $company['legal_name'] ?? 'Empresa sem nome' }}</p>
                                @if(isset($company['legal_name'], $company['trade_name']))<p class="text-slate-500">{{ $company['legal_name'] }}</p>@endif
                                @if(isset($company['tax_id']))<p class="text-slate-500">{{ $company['tax_id'] }}</p>@endif
                                @if(isset($company['city']) || isset($company['state']))<p class="text-slate-500">{{ implode(' / ', array_filter([$company['city'] ?? null, $company['state'] ?? null])) }}</p>@endif
                            @else — @endif
                        </td>
                        <td class="max-w-xs text-sm">
                            @if($contact !== [])
                                <p class="font-semibold text-slate-900">{{ $contact['name'] ?? 'Contato sem nome' }}</p>
                                @foreach(['job_title', 'email', 'phone', 'whatsapp'] as $field)@if(isset($contact[$field]))<p class="text-slate-500">{{ $contact[$field] }}</p>@endif @endforeach
                            @else — @endif
                        </td>
                        <td class="max-w-xs text-sm">
                            @if($lead !== [])
                                <p class="font-semibold text-slate-900">{{ $lead['name'] ?? $lead['company_name'] ?? 'Lead sem nome' }}</p>
                                @foreach(['email', 'phone', 'status', 'priority'] as $field)@if(isset($lead[$field]))<p class="text-slate-500">{{ $lead[$field] }}</p>@endif @endforeach
                            @else — @endif
                        </td>
                        <td><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ ['valid' => 'bg-emerald-100 text-emerald-800', 'warning' => 'bg-amber-100 text-amber-800', 'error' => 'bg-red-100 text-red-800'][$row['status']] }}">{{ ['valid' => 'Válido', 'warning' => 'Aviso', 'error' => 'Erro'][$row['status']] }}</span></td>
                        <td class="text-sm">{{ $row['issues'] === [] ? 'Nenhum problema detectado.' : count($row['issues']).' problema(s)' }}</td>
                        <td>
                            <details class="min-w-72">
                                <summary class="cursor-pointer text-sm font-semibold text-teal-700">Ver detalhes</summary>
                                <div class="mt-3 space-y-4 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm">
                                    <section><h3 class="font-bold text-slate-900">Dado original</h3><dl class="mt-2 space-y-1">@foreach($row['original_data'] as $key => $value)<div><dt class="inline font-medium">{{ $key }}:</dt> <dd class="inline whitespace-pre-wrap">{{ is_bool($value) ? ($value ? 'true' : 'false') : (is_scalar($value) ? (string) $value : 'Valor estruturado') }}</dd></div>@endforeach</dl></section>
                                    <section><h3 class="font-bold text-slate-900">Dado normalizado</h3>@forelse($row['data'] as $group => $fields)<h4 class="mt-2 font-semibold">{{ ucfirst($group) }}</h4><dl class="space-y-1">@foreach($fields as $field => $value)<div><dt class="inline font-medium">{{ $field }}:</dt> <dd class="inline whitespace-pre-wrap">{{ is_bool($value) ? ($value ? 'true' : 'false') : (is_scalar($value) ? (string) $value : 'Valor estruturado') }}</dd></div>@endforeach</dl>@empty<p class="mt-2 text-slate-500">Nenhum dado normalizado.</p>@endforelse</section>
                                    <section><h3 class="font-bold text-slate-900">Problemas</h3>@forelse($row['issues'] as $issue)<p class="mt-2"><span class="font-semibold">{{ $issue['field'] }}</span> — {{ $issue['message'] }} <span class="text-slate-500">({{ $issue['code'] }})</span></p>@empty<p class="mt-2 text-emerald-700">Nenhum problema detectado.</p>@endforelse</section>
                                </div>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-10 text-center text-slate-500">Nenhuma linha corresponde ao filtro selecionado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $rows->links() }}</div>

    <div class="mt-6 flex flex-wrap items-center gap-3">
        @can('update', $dataImport)<a class="btn-secondary" href="{{ route('imports.mapping.edit', $dataImport) }}">Voltar ao mapeamento</a>@endcan
        <button class="btn-primary cursor-not-allowed opacity-50" type="button" disabled>Continuar para deduplicação</button>
        <span class="text-sm text-slate-500">Disponível na TASK-084.</span>
    </div>
</x-layouts.app>
