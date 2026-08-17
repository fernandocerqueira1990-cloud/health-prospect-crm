<x-layouts.app title="Pipeline">
    <x-page-header
        title="Pipeline"
        description="Acompanhe as oportunidades em cada etapa do funil comercial."
    >
        <x-slot:actions>
            <div class="flex flex-wrap gap-3">
                <a
                    class="btn-secondary"
                    href="{{ route('opportunities.index') }}"
                >
                    Ver lista
                </a>

                @can('create', App\Models\Opportunity::class)
                    <a
                        class="btn-primary"
                        href="{{ route('opportunities.create') }}"
                    >
                        Nova oportunidade
                    </a>
                @endcan
            </div>
        </x-slot:actions>
    </x-page-header>

    <form
        class="card mb-6"
        method="GET"
        action="{{ route('roadmap.pipeline') }}"
    >
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="sm:col-span-2">
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
        </div>

        <div class="mt-5 flex flex-wrap gap-3">
            <button class="btn-primary" type="submit">
                Filtrar
            </button>

            <a
                class="btn-secondary"
                href="{{ route('roadmap.pipeline') }}"
            >
                Limpar
            </a>
        </div>
    </form>

    <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
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
            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                <p class="text-xs font-semibold uppercase text-slate-500">
                    Oportunidades
                </p>

                <p class="mt-1 text-lg font-bold text-slate-950">
                    {{ $boardCount }}
                </p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
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
        class="overflow-x-auto pb-5"
        data-csrf-token="{{ csrf_token() }}"
    >
        <div class="flex min-w-max items-start gap-4">
            @foreach($stages as $stage)
                @php
                    $cards = $opportunitiesByStage->get(
                        $stage->id,
                        collect()
                    );

                    $columnValue = $cards->sum(
                        fn ($opportunity) => (float) $opportunity->amount
                    );

                    $headerClass = match($stage->type) {
                        'won' => 'border-emerald-300 bg-emerald-50',
                        'lost' => 'border-red-300 bg-red-50',
                        default => 'border-slate-200 bg-slate-50',
                    };
                @endphp

                <section
                    class="w-80 shrink-0 overflow-hidden rounded-xl border border-slate-200 bg-slate-100 transition"
                    data-kanban-stage
                    data-stage-id="{{ $stage->id }}"
                    data-stage-type="{{ $stage->type }}"
                >
                    <header class="border-b p-4 {{ $headerClass }}">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h3 class="font-bold text-slate-950">
                                    {{ $stage->name }}
                                </h3>

                                <p class="mt-1 text-xs text-slate-500">
                                    Probabilidade {{ $stage->probability }}%
                                </p>
                            </div>

                            <span
                                class="flex h-8 min-w-8 items-center justify-center rounded-full bg-white px-2 text-xs font-bold text-slate-700 shadow-sm"
                            >
                                {{ $cards->count() }}
                            </span>
                        </div>

                        <p class="mt-3 text-xs font-semibold text-slate-600">
                            BRL
                            {{
                                number_format(
                                    $columnValue,
                                    2,
                                    ',',
                                    '.'
                                )
                            }}
                        </p>
                    </header>

                    <div
                        class="min-h-32 space-y-3 p-3"
                        data-kanban-dropzone
                    >
                        @forelse($cards as $opportunity)
                            <article
                                class="cursor-grab rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-teal-300 hover:shadow-md active:cursor-grabbing"
                                draggable="true"
                                data-kanban-card
                                data-opportunity-id="{{ $opportunity->id }}"
                                data-current-stage-id="{{ $opportunity->stage_id }}"
                                data-move-url="{{ route('opportunities.move-stage', $opportunity) }}"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <a
                                        class="font-semibold leading-snug text-slate-950 hover:text-teal-700"
                                        href="{{ route('opportunities.show', $opportunity) }}"
                                    >
                                        {{ $opportunity->title }}
                                    </a>

                                    <span
                                        class="shrink-0 rounded-full bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-600"
                                    >
                                        {{ $opportunity->probability }}%
                                    </span>
                                </div>

                                <p class="mt-2 text-sm text-slate-500">
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

                                <div
                                    class="mt-4 border-t border-slate-100 pt-3"
                                >
                                    <p class="font-bold text-slate-950">
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

                                    <div
                                        class="mt-3 flex items-center justify-between gap-2 text-xs text-slate-500"
                                    >
                                        <span>
                                            {{
                                                $opportunity->assignedUser?->name
                                                ?? 'Não atribuído'
                                            }}
                                        </span>

                                        <span>
                                            {{
                                                $opportunity
                                                    ->expected_close_date
                                                    ?->format('d/m/Y')
                                                ?? 'Sem previsão'
                                            }}
                                        </span>
                                    </div>
                                </div>

                                @if($opportunity->lossReason)
                                    <div
                                        class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-xs font-medium text-red-700"
                                    >
                                        {{ $opportunity->lossReason->name }}
                                    </div>
                                @endif

                                <div class="mt-4">
                                    <a
                                        class="text-sm font-semibold text-teal-700 hover:text-teal-900"
                                        href="{{ route('opportunities.show', $opportunity) }}"
                                    >
                                        Abrir oportunidade →
                                    </a>
                                </div>
                            </article>
                        @empty
                            <div
                                class="rounded-xl border border-dashed border-slate-300 bg-white/60 p-6 text-center"
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
