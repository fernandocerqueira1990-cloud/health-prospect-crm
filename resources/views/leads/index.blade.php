<x-layouts.app title="Leads">
    <x-page-header
        title="Leads"
        description="Captação, qualificação, origem e acompanhamento comercial."
    >
        <x-slot:actions>
            @can('create', App\Models\Lead::class)
                <a class="btn-primary" href="{{ route('leads.create') }}">
                    Novo lead
                </a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <form class="card mb-6" method="GET" action="{{ route('leads.index') }}">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

            <div class="sm:col-span-2">
                <label class="label" for="q">Busca geral</label>
                <input
                    class="input"
                    id="q"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Nome, empresa, cargo, e-mail, telefone ou WhatsApp"
                >
            </div>

            <div>
                <label class="label" for="status">Status</label>
                <select class="input" id="status" name="status">
                    <option value="">Todos</option>

                    @foreach([
                        'new' => 'Novo',
                        'contacted' => 'Contatado',
                        'qualified' => 'Qualificado',
                        'nurturing' => 'Nutrição',
                        'converted' => 'Convertido',
                        'disqualified' => 'Desqualificado',
                    ] as $value => $label)
                        <option
                            value="{{ $value }}"
                            @selected(request('status') === $value)
                        >
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="label" for="assigned_user_id">Responsável</label>
                <select class="input" id="assigned_user_id" name="assigned_user_id">
                    <option value="">Todos</option>

                    @foreach($assignedUsers as $user)
                        <option
                            value="{{ $user->id }}"
                            @selected((string) request('assigned_user_id') === (string) $user->id)
                        >
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="label" for="source_id">Origem</label>
                <select class="input" id="source_id" name="source_id">
                    <option value="">Todas</option>

                    @foreach($sources as $source)
                        <option
                            value="{{ $source->id }}"
                            @selected((string) request('source_id') === (string) $source->id)
                        >
                            {{ $source->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="label" for="channel_id">Canal</label>
                <select class="input" id="channel_id" name="channel_id">
                    <option value="">Todos</option>

                    @foreach($channels as $channel)
                        <option
                            value="{{ $channel->id }}"
                            @selected((string) request('channel_id') === (string) $channel->id)
                        >
                            {{ $channel->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="label" for="priority">Prioridade</label>
                <select class="input" id="priority" name="priority">
                    <option value="">Todas</option>

                    @foreach([
                        'low' => 'Baixa',
                        'medium' => 'Média',
                        'high' => 'Alta',
                        'critical' => 'Crítica',
                    ] as $value => $label)
                        <option
                            value="{{ $value }}"
                            @selected(request('priority') === $value)
                        >
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="label" for="temperature">Temperatura</label>
                <select class="input" id="temperature" name="temperature">
                    <option value="">Todas</option>

                    @foreach([
                        'cold' => 'Frio',
                        'warm' => 'Morno',
                        'hot' => 'Quente',
                    ] as $value => $label)
                        <option
                            value="{{ $value }}"
                            @selected(request('temperature') === $value)
                        >
                            {{ $label }}
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
                            @selected((int) request('per_page', 15) === $value)
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

            <a class="btn-secondary" href="{{ route('leads.index') }}">
                Limpar
            </a>
        </div>
    </form>

    @php
        $statusLabels = [
            'new' => 'Novo',
            'contacted' => 'Contatado',
            'qualified' => 'Qualificado',
            'nurturing' => 'Nutrição',
            'converted' => 'Convertido',
            'disqualified' => 'Desqualificado',
        ];

        $statusClasses = [
            'new' => 'bg-blue-100 text-blue-800',
            'contacted' => 'bg-cyan-100 text-cyan-800',
            'qualified' => 'bg-emerald-100 text-emerald-800',
            'nurturing' => 'bg-violet-100 text-violet-800',
            'converted' => 'bg-teal-100 text-teal-800',
            'disqualified' => 'bg-slate-200 text-slate-700',
        ];

        $temperatureLabels = [
            'cold' => 'Frio',
            'warm' => 'Morno',
            'hot' => 'Quente',
        ];

        $temperatureClasses = [
            'cold' => 'bg-slate-100 text-slate-700',
            'warm' => 'bg-amber-100 text-amber-800',
            'hot' => 'bg-red-100 text-red-800',
        ];
    @endphp

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500">
            @if($leads->total() > 0)
                Mostrando {{ $leads->firstItem() }}–{{ $leads->lastItem() }}
                de {{ $leads->total() }} leads
            @else
                Nenhum lead encontrado
            @endif
        </p>
    </div>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Lead</th>
                    <th>Origem / Canal</th>
                    <th>Status</th>
                    <th>Temperatura</th>
                    <th>Score</th>
                    <th>Responsável</th>
                    <th>Próxima ação</th>
                    <th></th>
                </tr>
            </thead>

            <tbody>
                @forelse($leads as $lead)
                    <tr>
                        <td>
                            <a
                                class="font-semibold text-teal-700 hover:text-teal-900"
                                href="{{ route('leads.show', $lead) }}"
                            >
                                {{ $lead->name ?: 'Lead #'.$lead->id }}
                            </a>

                            <br>

                            <span class="text-sm text-slate-500">
                                @if($lead->company)
                                    {{ $lead->company->trade_name ?: $lead->company->legal_name }}
                                @elseif($lead->company_name)
                                    {{ $lead->company_name }}
                                @else
                                    Empresa não informada
                                @endif
                            </span>

                            @if($lead->job_title)
                                <br>
                                <span class="text-xs text-slate-400">
                                    {{ $lead->job_title }}
                                </span>
                            @endif
                        </td>

                        <td>
                            <span class="font-medium text-slate-800">
                                {{ $lead->source?->name ?? '—' }}
                            </span>

                            <br>

                            <span class="text-xs text-slate-500">
                                {{ $lead->channel?->name ?? '—' }}
                            </span>
                        </td>

                        <td>
                            <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $statusClasses[$lead->status] ?? 'bg-slate-100 text-slate-700' }}">
                                {{ $statusLabels[$lead->status] ?? $lead->status }}
                            </span>
                        </td>

                        <td>
                            @if($lead->temperature)
                                <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $temperatureClasses[$lead->temperature] ?? 'bg-slate-100 text-slate-700' }}">
                                    {{ $temperatureLabels[$lead->temperature] ?? $lead->temperature }}
                                </span>
                            @else
                                —
                            @endif
                        </td>

                        <td>
                            <span class="font-semibold text-slate-900">
                                {{ $lead->score }}
                            </span>
                            <span class="text-xs text-slate-400">/100</span>
                        </td>

                        <td>
                            {{ $lead->assignedUser?->name ?? 'Não atribuído' }}
                        </td>

                        <td>
                            @if($lead->next_action_at)
                                {{ $lead->next_action_at->format('d/m/Y H:i') }}
                            @else
                                —
                            @endif
                        </td>

                        <td class="text-right">
                            <div class="flex justify-end gap-3">
                                @can('view', $lead)
                                    <a
                                        class="font-semibold text-teal-700 hover:text-teal-900"
                                        href="{{ route('leads.show', $lead) }}"
                                    >
                                        Ver
                                    </a>
                                @endcan

                                @can('update', $lead)
                                    <a
                                        class="font-semibold text-teal-700 hover:text-teal-900"
                                        href="{{ route('leads.edit', $lead) }}"
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
                            Nenhum lead encontrado.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">
        {{ $leads->links() }}
    </div>
</x-layouts.app>
