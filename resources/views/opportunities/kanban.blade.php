<x-layouts.app title="Pipeline">
    <x-page-header
        title="Pipeline"
        description="Acompanhe as oportunidades em cada etapa do funil comercial."
    >
        <x-slot:actions>
            <a
                class="btn-secondary"
                href="{{ route('opportunities.index') }}"
            >
                Ver lista
            </a>
        </x-slot:actions>
    </x-page-header>

    <form
        id="pipeline-filter-form"
        class="mb-4 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm"
        method="GET"
        action="{{ route('roadmap.pipeline') }}"
    >
        <div class="grid items-end gap-2.5 md:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-[minmax(14rem,1fr)_11rem_12rem_auto_auto_auto_auto]">
            <div>
                <label class="label" for="q">
                    Busca geral
                </label>

                <input
                    class="input"
                    id="q"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Oportunidade, empresa ou lead"
                >
            </div>

            <div>
                <label class="label" for="pipeline_id">
                    Pipeline
                </label>

                <select
                    class="input"
                    id="pipeline_id"
                    name="pipeline_id"
                >
                    @foreach($pipelines as $item)
                        <option
                            value="{{ $item->id }}"
                            @selected(
                                (string) $pipeline->id === (string) $item->id
                            )
                        >
                            {{ $item->name }}
                            {{ $item->is_default ? ' — Padrão' : '' }}
                        </option>
                    @endforeach
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
            <button class="btn-primary whitespace-nowrap" type="submit">
                Filtrar
            </button>

            <a
                class="btn-secondary whitespace-nowrap"
                href="{{ route('roadmap.pipeline') }}"
            >
                Limpar
            </a>

            <button
                class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50 hover:text-slate-950"
                type="button"
                data-pipeline-filter-open
                aria-controls="pipeline-filter-drawer"
                aria-expanded="false"
            >
                <span aria-hidden="true">☷</span>
                Filtros avançados
            </button>

            @can('create', App\Models\Opportunity::class)
                <a
                    class="btn-primary whitespace-nowrap"
                    href="{{ route('opportunities.create') }}"
                >
                    <span aria-hidden="true">＋</span>
                    Nova oportunidade
                </a>
            @endcan
        </div>
    </form>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-bold text-slate-950">
                {{ $pipeline->name }}
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                {{ $stages->count() }} etapas no funil
            </p>
        </div>

        @php
            $boardCount = $opportunitiesByStage
                ->flatten(1)
                ->count();

            $boardValue = $opportunitiesByStage
                ->flatten(1)
                ->sum(fn ($opportunity) => (float) $opportunity->amount);
        @endphp

        <div class="flex flex-wrap gap-3">
            <div class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 shadow-sm">
                <p class="text-xs font-semibold uppercase text-slate-500">
                    Oportunidades
                </p>

                <p class="mt-1 text-lg font-bold text-slate-950">
                    {{ $boardCount }}
                </p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 shadow-sm">
                <p class="text-xs font-semibold uppercase text-slate-500">
                    Valor total
                </p>

                <p class="mt-1 text-lg font-bold text-slate-950">
                    BRL
                    {{
                        number_format(
                            $boardValue,
                            2,
                            ',',
                            '.'
                        )
                    }}
                </p>
            </div>
        </div>
    </div>

    <div
        id="pipeline-kanban"
        class="pipeline-board pb-3"
        data-csrf-token="{{ csrf_token() }}"
    >
        <div class="flex min-w-max items-start gap-3 p-1 pb-3">
            @foreach($stages as $stage)
                @php
                    $cards = $opportunitiesByStage->get(
                        $stage->id,
                        collect()
                    );

                    $columnValue = $cards->sum(
                        fn ($opportunity) => (float) $opportunity->amount
                    );

                    $stageTheme = match($stage->type) {
                        'won' => 'border-emerald-300 bg-emerald-50 text-emerald-800',
                        'lost' => 'border-red-300 bg-red-50 text-red-800',
                        default => match($loop->index % 4) {
                            0 => 'border-blue-300 bg-blue-50 text-blue-800',
                            1 => 'border-amber-300 bg-amber-50 text-amber-800',
                            2 => 'border-orange-300 bg-orange-50 text-orange-800',
                            default => 'border-violet-300 bg-violet-50 text-violet-800',
                        },
                    };

                    $stageAccent = match($stage->type) {
                        'won' => 'bg-emerald-500',
                        'lost' => 'bg-red-500',
                        default => match($loop->index % 4) {
                            0 => 'bg-blue-500',
                            1 => 'bg-amber-500',
                            2 => 'bg-orange-500',
                            default => 'bg-violet-500',
                        },
                    };
                @endphp

                <section
                    class="kanban-column w-72 shrink-0 rounded-2xl border border-slate-200 bg-slate-100/80 shadow-sm transition xl:w-[17.5rem]"
                    data-kanban-stage
                    data-stage-id="{{ $stage->id }}"
                    data-stage-type="{{ $stage->type }}"
                >
                    <header class="kanban-column-header sticky top-0 z-10 border-b p-3.5 {{ $stageTheme }}">
                        <span class="absolute inset-x-0 top-0 h-1 {{ $stageAccent }}" aria-hidden="true"></span>

                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="truncate text-sm font-extrabold uppercase tracking-wide">
                                    {{ $stage->name }}
                                </h3>

                                <p class="mt-1 text-[11px] font-medium opacity-70">
                                    Probabilidade {{ $stage->probability }}%
                                </p>
                            </div>

                            <span
                                class="flex h-7 min-w-7 items-center justify-center rounded-full bg-white/90 px-2 text-xs font-extrabold shadow-sm"
                                title="{{ $cards->count() }} oportunidades"
                            >
                                {{ $cards->count() }}
                            </span>
                        </div>

                        <div class="mt-3 flex items-center justify-between border-t border-current/10 pt-2.5">
                            <span class="text-[10px] font-bold uppercase tracking-wider opacity-60">Valor da etapa</span>
                            <span class="text-sm font-extrabold">
                                BRL {{ number_format($columnValue, 2, ',', '.') }}
                            </span>
                        </div>
                    </header>

                    <div
                        class="min-h-24 space-y-2.5 p-2.5"
                        data-kanban-dropzone
                    >
                        @forelse($cards as $opportunity)
                            <article
                                class="group cursor-grab rounded-xl border border-slate-200 bg-white p-3 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-teal-300 hover:shadow-md active:cursor-grabbing"
                                draggable="true"
                                data-kanban-card
                                data-opportunity-id="{{ $opportunity->id }}"
                                data-current-stage-id="{{ $opportunity->stage_id }}"
                                data-move-url="{{ route('opportunities.move-stage', $opportunity) }}"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <a
                                        class="text-sm font-bold leading-snug text-slate-950 transition group-hover:text-teal-700"
                                        href="{{ route('opportunities.show', $opportunity) }}"
                                    >
                                        {{ $opportunity->title }}
                                    </a>

                                    <span
                                        class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600"
                                    >
                                        {{ $opportunity->probability }}%
                                    </span>
                                </div>

                                <p class="mt-1.5 truncate text-xs font-medium text-slate-500">
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
                                        Sem empresa
                                    @endif
                                </p>

                                <div class="mt-3 border-t border-slate-100 pt-2.5">
                                    <p class="text-sm font-extrabold text-slate-950">
                                        {{ $opportunity->currency }}
                                        {{
                                            number_format(
                                                (float) $opportunity->amount,
                                                2,
                                                ',',
                                                '.'
                                            )
                                        }}
                                    </p>

                                    <div class="mt-2.5 space-y-1.5 text-[11px] text-slate-500">
                                        <div class="flex items-center gap-2">
                                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[9px] font-bold text-slate-600" aria-hidden="true">R</span>
                                            <span class="truncate">
                                            {{
                                                $opportunity->assignedUser?->name
                                                ?? 'Não atribuído'
                                            }}
                                            </span>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[10px] text-slate-600" aria-hidden="true">◷</span>
                                            <span>{{ $opportunity->expected_close_date?->format('d/m/Y') ?? 'Sem previsão' }}</span>
                                        </div>
                                    </div>
                                </div>

                                @if($opportunity->lossReason)
                                    <div
                                        class="mt-2.5 rounded-lg bg-red-50 px-2.5 py-2 text-[11px] font-medium text-red-700"
                                    >
                                        {{ $opportunity->lossReason->name }}
                                    </div>
                                @endif

                                <div class="mt-3 flex justify-end">
                                    <a
                                        class="text-xs font-bold text-teal-700 hover:text-teal-900"
                                        href="{{ route('opportunities.show', $opportunity) }}"
                                    >
                                        Abrir oportunidade →
                                    </a>
                                </div>
                            </article>
                        @empty
                            <div
                                class="rounded-xl border border-dashed border-slate-300 bg-white/60 p-4 text-center"
                            >
                                <p class="text-sm font-medium text-slate-500">
                                    Nenhuma oportunidade
                                </p>
                            </div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    </div>

    <div class="pipeline-filter-backdrop" data-pipeline-filter-backdrop></div>

    <aside
        id="pipeline-filter-drawer"
        class="pipeline-filter-drawer"
        data-pipeline-filter-drawer
        aria-hidden="true"
        aria-label="Filtros avançados do Pipeline"
        inert
    >
        <div class="flex h-20 items-center justify-between border-b border-slate-200 px-5">
            <div>
                <p class="font-bold text-slate-950">Filtros avançados</p>
                <p class="mt-0.5 text-xs text-slate-500">Personalize a visualização do funil</p>
            </div>
            <button class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-950" type="button" data-pipeline-filter-close aria-label="Fechar filtros">✕</button>
        </div>

        <div class="flex-1 overflow-y-auto p-5">
            <fieldset>
                <legend class="text-xs font-bold uppercase tracking-wider text-slate-500">Etapas visíveis</legend>
                <div class="mt-3 space-y-2">
                    @foreach($stages as $stage)
                        <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-slate-200 px-3 py-2.5 transition hover:border-teal-300 hover:bg-teal-50/40">
                            <span class="text-sm font-semibold text-slate-700">{{ $stage->name }}</span>
                            <input class="h-4 w-4 rounded border-slate-300 text-teal-700 focus:ring-teal-600" type="checkbox" checked data-stage-visibility value="{{ $stage->id }}">
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div class="mt-6 border-t border-slate-200 pt-5">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Consulta atual</p>
                <dl class="mt-3 space-y-3 rounded-xl bg-slate-50 p-4 text-sm">
                    <div>
                        <dt class="text-xs text-slate-500">Pipeline</dt>
                        <dd class="mt-0.5 font-semibold text-slate-800">{{ $pipeline->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Responsável</dt>
                        <dd class="mt-0.5 font-semibold text-slate-800">{{ $assignedUsers->firstWhere('id', (int) request('assigned_user_id'))?->name ?? 'Todos' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Busca</dt>
                        <dd class="mt-0.5 break-words font-semibold text-slate-800">{{ request('q') ?: 'Sem termo informado' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="mt-6 rounded-xl border border-dashed border-slate-300 p-4">
                <p class="text-sm font-semibold text-slate-700">Mais critérios</p>
                <p class="mt-1 text-xs leading-5 text-slate-500">Origem, tipo de contato, período, probabilidade e faixa de valor poderão ser adicionados aqui quando estiverem disponíveis na consulta do Pipeline.</p>
            </div>
        </div>

        <div class="border-t border-slate-200 p-4">
            <button class="btn-primary w-full" type="button" data-pipeline-filter-close>Aplicar visualização</button>
        </div>
    </aside>

    <div
        id="loss-reason-modal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4"
        aria-hidden="true"
    >
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
            <h2 class="text-lg font-bold text-slate-950">
                Marcar oportunidade como perdida
            </h2>

            <p class="mt-2 text-sm text-slate-500">
                Informe o motivo antes de mover a oportunidade para Perdido.
            </p>

            <div class="mt-5">
                <label class="label" for="kanban-loss-reason">
                    Motivo da perda *
                </label>

                <select
                    class="input"
                    id="kanban-loss-reason"
                >
                    <option value="">Selecione</option>

                    @foreach($lossReasons as $reason)
                        <option value="{{ $reason->id }}">
                            {{ $reason->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mt-5">
                <label class="label" for="kanban-loss-notes">
                    Observação
                </label>

                <textarea
                    class="input min-h-24"
                    id="kanban-loss-notes"
                    maxlength="10000"
                    placeholder="Ex.: cliente optou por concorrente..."
                ></textarea>
            </div>

            <p
                id="kanban-loss-error"
                class="mt-3 hidden text-sm font-medium text-red-600"
            >
                Selecione um motivo da perda.
            </p>

            <div class="mt-6 flex justify-end gap-3">
                <button
                    class="btn-secondary"
                    id="kanban-loss-cancel"
                    type="button"
                >
                    Cancelar
                </button>

                <button
                    class="btn-primary"
                    id="kanban-loss-confirm"
                    type="button"
                >
                    Confirmar perda
                </button>
            </div>
        </div>
    </div>

</x-layouts.app>
