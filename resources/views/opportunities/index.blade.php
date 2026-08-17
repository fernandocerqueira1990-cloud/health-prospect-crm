<x-layouts.app title="Oportunidades">
    <x-page-header
        title="Oportunidades"
        description="Negócios em andamento, valores, etapas e previsão de fechamento."
    >
        <x-slot:actions>
            @can('create', App\Models\Opportunity::class)
                <a class="btn-primary" href="{{ route('opportunities.create') }}">
                    Nova oportunidade
                </a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <form
        class="card mb-6"
        method="GET"
        action="{{ route('opportunities.index') }}"
    >
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="sm:col-span-2">
                <label class="label" for="q">Busca geral</label>

                <input
                    class="input"
                    id="q"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Oportunidade, empresa, lead ou contato"
                >
            </div>

            <div>
                <label class="label" for="pipeline_id">Pipeline</label>

                <select class="input" id="pipeline_id" name="pipeline_id">
                    <option value="">Todos</option>

                    @foreach($pipelines as $pipeline)
                        <option
                            value="{{ $pipeline->id }}"
                            @selected(
                                (string) request('pipeline_id')
                                === (string) $pipeline->id
                            )
                        >
                            {{ $pipeline->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="label" for="stage_id">Etapa</label>

                <select class="input" id="stage_id" name="stage_id">
                    <option value="">Todas</option>

                    @foreach($stages as $stage)
                        <option
                            value="{{ $stage->id }}"
                            @selected(
                                (string) request('stage_id')
                                === (string) $stage->id
                            )
                        >
                            {{ $stage->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="label" for="state">Situação</label>

                <select class="input" id="state" name="state">
                    <option value="">Todas</option>
                    <option value="open" @selected(request('state') === 'open')>
                        Em aberto
                    </option>
                    <option value="won" @selected(request('state') === 'won')>
                        Ganho
                    </option>
                    <option value="lost" @selected(request('state') === 'lost')>
                        Perdido
                    </option>
                </select>
            </div>

            <div>
                <label class="label" for="assigned_user_id">
                    Responsável
                </label>

                <select
                    class="input"
                    id="assigned_user_id"
                    name="assigned_user_id"
                >
                    <option value="">Todos</option>

                    @foreach($assignedUsers as $user)
                        <option
                            value="{{ $user->id }}"
                            @selected(
                                (string) request('assigned_user_id')
                                === (string) $user->id
                            )
                        >
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="label" for="per_page">Por página</label>

                <select class="input" id="per_page" name="per_page">
                    @foreach([10, 15, 25, 50, 100] as $value)
                        <option
                            value="{{ $value }}"
                            @selected(
                                (int) request('per_page', 15) === $value
                            )
                        >
                            {{ $value }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-5 flex flex-wrap gap-3">
            <button class="btn-primary" type="submit">
                Filtrar
            </button>

            <a
                class="btn-secondary"
                href="{{ route('opportunities.index') }}"
            >
                Limpar
            </a>
        </div>
    </form>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500">
            @if($opportunities->total() > 0)
                Mostrando
                {{ $opportunities->firstItem() }}–{{ $opportunities->lastItem() }}
                de {{ $opportunities->total() }} oportunidades
            @else
                Nenhuma oportunidade encontrada
            @endif
        </p>
    </div>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Oportunidade</th>
                    <th>Pipeline / Etapa</th>
                    <th>Valor</th>
                    <th>Probabilidade</th>
                    <th>Responsável</th>
                    <th>Fechamento previsto</th>
                    <th>Situação</th>
                    <th></th>
                </tr>
            </thead>

            <tbody>
                @forelse($opportunities as $opportunity)
                    @php
                        $stateLabel = match($opportunity->stage?->type) {
                            'won' => 'Ganho',
                            'lost' => 'Perdido',
                            default => 'Em aberto',
                        };

                        $stateClass = match($opportunity->stage?->type) {
                            'won' => 'bg-emerald-100 text-emerald-800',
                            'lost' => 'bg-red-100 text-red-800',
                            default => 'bg-blue-100 text-blue-800',
                        };
                    @endphp

                    <tr>
                        <td>
                            <a
                                class="font-semibold text-teal-700 hover:text-teal-900"
                                href="{{ route('opportunities.show', $opportunity) }}"
                            >
                                {{ $opportunity->title }}
                            </a>

                            <br>

                            <span class="text-sm text-slate-500">
                                @if($opportunity->company)
                                    {{
                                        $opportunity->company->trade_name
                                        ?: $opportunity->company->legal_name
                                    }}
                                @elseif($opportunity->lead)
                                    {{
                                        $opportunity->lead->company_name
                                        ?: $opportunity->lead->name
                                        ?: 'Lead #'.$opportunity->lead->id
                                    }}
                                @else
                                    Sem empresa informada
                                @endif
                            </span>
                        </td>

                        <td>
                            <span class="font-medium text-slate-800">
                                {{ $opportunity->pipeline?->name ?? '—' }}
                            </span>

                            <br>

                            <span class="text-xs text-slate-500">
                                {{ $opportunity->stage?->name ?? '—' }}
                            </span>
                        </td>

                        <td>
                            <span class="font-semibold text-slate-900">
                                {{ $opportunity->currency }}
                                {{
                                    number_format(
                                        (float) $opportunity->amount,
                                        2,
                                        ',',
                                        '.'
                                    )
                                }}
                            </span>
                        </td>

                        <td>
                            <span class="font-semibold text-slate-900">
                                {{ $opportunity->probability }}%
                            </span>
                        </td>

                        <td>
                            {{
                                $opportunity->assignedUser?->name
                                ?? 'Não atribuído'
                            }}
                        </td>

                        <td>
                            @if($opportunity->expected_close_date)
                                {{
                                    $opportunity->expected_close_date
                                        ->format('d/m/Y')
                                }}
                            @else
                                —
                            @endif
                        </td>

                        <td>
                            <span
                                class="rounded-full px-2 py-1 text-xs font-semibold {{ $stateClass }}"
                            >
                                {{ $stateLabel }}
                            </span>

                            @if($opportunity->lossReason)
                                <br>
                                <span class="mt-1 inline-block text-xs text-slate-500">
                                    {{ $opportunity->lossReason->name }}
                                </span>
                            @endif
                        </td>

                        <td class="text-right">
                            <div class="flex justify-end gap-3">
                                @can('view', $opportunity)
                                    <a
                                        class="font-semibold text-teal-700 hover:text-teal-900"
                                        href="{{ route('opportunities.show', $opportunity) }}"
                                    >
                                        Ver
                                    </a>
                                @endcan

                                @can('update', $opportunity)
                                    <a
                                        class="font-semibold text-teal-700 hover:text-teal-900"
                                        href="{{ route('opportunities.edit', $opportunity) }}"
                                    >
                                        Editar
                                    </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">
                            Nenhuma oportunidade encontrada.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">
        {{ $opportunities->links() }}
    </div>
</x-layouts.app>
