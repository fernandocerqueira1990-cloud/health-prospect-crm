<x-layouts.app title="Atividades">
    <x-page-header
        title="Atividades"
        description="Histórico das interações comerciais realizadas."
    >
        <x-slot:actions>
            @can('create', App\Models\Activity::class)
                <a class="btn-primary" href="{{ route('activities.create') }}">
                    Nova atividade
                </a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <form
        class="card mb-6"
        method="GET"
        action="{{ route('activities.index') }}"
    >
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="sm:col-span-2">
                <label class="label" for="q">Busca geral</label>

                <input
                    class="input"
                    id="q"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Assunto, descrição, empresa, lead..."
                >
            </div>

            <div>
                <label class="label" for="type">Tipo</label>

                <select class="input" id="type" name="type">
                    <option value="">Todos</option>

                    @foreach([
                        'call' => 'Ligação',
                        'email' => 'E-mail',
                        'whatsapp' => 'WhatsApp',
                        'meeting' => 'Reunião',
                        'note' => 'Nota',
                        'other' => 'Outro',
                    ] as $value => $label)
                        <option
                            value="{{ $value }}"
                            @selected(request('type') === $value)
                        >
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="label" for="direction">Direção</label>

                <select class="input" id="direction" name="direction">
                    <option value="">Todas</option>
                    <option
                        value="outbound"
                        @selected(request('direction') === 'outbound')
                    >
                        Saída
                    </option>
                    <option
                        value="inbound"
                        @selected(request('direction') === 'inbound')
                    >
                        Entrada
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
                <label class="label" for="date_from">De</label>

                <input
                    class="input"
                    id="date_from"
                    name="date_from"
                    type="date"
                    value="{{ request('date_from') }}"
                >
            </div>

            <div>
                <label class="label" for="date_to">Até</label>

                <input
                    class="input"
                    id="date_to"
                    name="date_to"
                    type="date"
                    value="{{ request('date_to') }}"
                >
            </div>

            <div>
                <label class="label" for="per_page">Por página</label>

                <select class="input" id="per_page" name="per_page">
                    @foreach([15, 30, 50, 100] as $value)
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

            <a class="btn-secondary" href="{{ route('activities.index') }}">
                Limpar
            </a>
        </div>
    </form>

    <div class="mb-4 text-sm text-slate-500">
        @if($activities->total() > 0)
            Mostrando
            {{ $activities->firstItem() }}–{{ $activities->lastItem() }}
            de {{ $activities->total() }} atividades
        @else
            Nenhuma atividade encontrada
        @endif
    </div>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Atividade</th>
                    <th>Vínculo</th>
                    <th>Responsável</th>
                    <th>Data</th>
                    <th>Duração</th>
                    <th></th>
                </tr>
            </thead>

            <tbody>
                @forelse($activities as $activity)
                    @php
                        $typeLabel = match($activity->type) {
                            'call' => 'Ligação',
                            'email' => 'E-mail',
                            'whatsapp' => 'WhatsApp',
                            'meeting' => 'Reunião',
                            'note' => 'Nota',
                            default => 'Outro',
                        };
                    @endphp

                    <tr>
                        <td>
                            <a
                                class="font-semibold text-teal-700 hover:text-teal-900"
                                href="{{ route('activities.show', $activity) }}"
                            >
                                {{ $activity->subject }}
                            </a>

                            <br>

                            <span class="text-sm text-slate-500">
                                {{ $typeLabel }}

                                @if($activity->direction === 'outbound')
                                    · Saída
                                @elseif($activity->direction === 'inbound')
                                    · Entrada
                                @endif
                            </span>
                        </td>

                        <td>
                            @if($activity->company)
                                {{
                                    $activity->company->trade_name
                                    ?: $activity->company->legal_name
                                }}
                            @elseif($activity->opportunity)
                                {{ $activity->opportunity->title }}
                            @elseif($activity->lead)
                                {{
                                    $activity->lead->name
                                    ?: $activity->lead->company_name
                                    ?: 'Lead #'.$activity->lead->id
                                }}
                            @elseif($activity->contact)
                                {{ $activity->contact->name }}
                            @else
                                —
                            @endif
                        </td>

                        <td>
                            {{ $activity->assignedUser?->name ?? 'Não atribuído' }}
                        </td>

                        <td>
                            {{ $activity->occurred_at?->format('d/m/Y H:i') ?? '—' }}
                        </td>

                        <td>
                            {{
                                $activity->duration_minutes
                                    ? $activity->duration_minutes.' min'
                                    : '—'
                            }}
                        </td>

                        <td class="text-right">
                            <div class="flex justify-end gap-3">
                                @can('view', $activity)
                                    <a
                                        class="font-semibold text-teal-700 hover:text-teal-900"
                                        href="{{ route('activities.show', $activity) }}"
                                    >
                                        Ver
                                    </a>
                                @endcan

                                @can('update', $activity)
                                    <a
                                        class="font-semibold text-teal-700 hover:text-teal-900"
                                        href="{{ route('activities.edit', $activity) }}"
                                    >
                                        Editar
                                    </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">
                            Nenhuma atividade encontrada.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">
        {{ $activities->links() }}
    </div>
</x-layouts.app>
