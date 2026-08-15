<x-layouts.app title="Mapear colunas">
    <x-page-header title="Mapear colunas" description="Defina o significado comercial das colunas de {{ $dataImport->original_filename }}.">
        <x-slot:actions><a class="btn-secondary" href="{{ route('imports.show', $dataImport) }}">Voltar</a></x-slot:actions>
    </x-page-header>

    <div class="card">
        <x-errors />
        <p class="mb-6 text-sm text-slate-600">Colunas ignoradas permanecem no arquivo original. Salvar reconstrói os dados normalizados sem criar empresas, contatos ou leads.</p>

        <form method="POST" action="{{ route('imports.mapping.update', $dataImport) }}">
            @csrf
            @method('PUT')

            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Coluna original</th><th>Exemplos</th><th>Destino</th></tr></thead>
                    <tbody>
                        @foreach($headers as $index => $header)
                            @php($selected = old("columns.$index.target", $selections[$header] ?? ''))
                            <tr>
                                <td class="font-semibold text-slate-900">
                                    {{ $header }}
                                    <input type="hidden" name="columns[{{ $index }}][source]" value="{{ $header }}">
                                </td>
                                <td class="max-w-sm text-sm text-slate-500">
                                    {{ $samples[$header] === [] ? 'Sem valores de exemplo' : implode(' · ', array_map(fn ($value) => Illuminate\Support\Str::limit($value, 60), $samples[$header])) }}
                                </td>
                                <td class="min-w-72">
                                    <label class="sr-only" for="target-{{ $index }}">Destino de {{ $header }}</label>
                                    <select class="input" id="target-{{ $index }}" name="columns[{{ $index }}][target]">
                                        <option value="">Ignorar</option>
                                        @foreach($groups as $group)
                                            <optgroup label="{{ mb_strtoupper($group['label']) }}">
                                                @foreach($group['fields'] as $target => $label)
                                                    <option value="{{ $target }}" @selected($selected === $target)>{{ $label }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <button class="btn-primary" type="submit">Salvar mapeamento</button>
                <a class="btn-secondary" href="{{ route('imports.show', $dataImport) }}">Cancelar</a>
            </div>
        </form>
    </div>
</x-layouts.app>
