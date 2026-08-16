<x-layouts.app title="Executar importação">
    <x-page-header title="Executar importação" description="Confirme a materialização dos dados revisados no CRM.">
        <x-slot:actions><a class="btn-secondary" href="{{ route('imports.dedup.index', $dataImport) }}">Voltar à deduplicação</a></x-slot:actions>
    </x-page-header>

    <div class="card max-w-3xl">
        <x-errors />
        <dl class="grid gap-4 text-sm sm:grid-cols-3">
            <div><dt class="font-medium text-slate-500">Arquivo</dt><dd class="mt-1 font-semibold">{{ $dataImport->original_filename }}</dd></div>
            <div><dt class="font-medium text-slate-500">Linhas</dt><dd class="mt-1">{{ $dedupSummary['total'] ?? $dataImport->total_rows }}</dd></div>
            <div><dt class="font-medium text-slate-500">Duplicados fortes</dt><dd class="mt-1">{{ $dedupSummary['exact_matches'] ?? $dataImport->duplicate_rows }}</dd></div>
        </dl>

        <form class="mt-7 space-y-5" method="POST" action="{{ route('imports.execute', $dataImport) }}">
            @csrf
            <div><span class="label">Origem</span><p class="input bg-slate-50">Importação</p></div>
            @if($needsLeadChannel)
                <div>
                    <label class="label" for="lead_channel_id">Canal dos novos Leads</label>
                    <select class="input" id="lead_channel_id" name="lead_channel_id" required>
                        <option value="">Selecione</option>
                        @foreach($channels as $channel)<option value="{{ $channel->id }}" @selected((string) old('lead_channel_id') === (string) $channel->id)>{{ $channel->name }}</option>@endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Selecione o canal de origem dos Leads deste arquivo.</p>
                </div>
            @else
                <p class="rounded-xl bg-slate-50 p-4 text-sm text-slate-600">Esta execução não criará novos Leads; nenhum canal precisa ser selecionado.</p>
            @endif
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">A execução criará entidades conforme as decisões persistidas. Registros existentes não serão atualizados e não haverá merge automático.</div>
            <button class="btn-primary" type="submit">Executar importação</button>
        </form>
    </div>
</x-layouts.app>
