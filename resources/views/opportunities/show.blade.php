<x-layouts.app :title="$opportunity->title">
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

    <x-page-header
        :title="$opportunity->title"
        :description="$opportunity->company?->trade_name
            ?: $opportunity->company?->legal_name
            ?: $opportunity->lead?->company_name
            ?: $opportunity->lead?->name
            ?: 'Oportunidade comercial'"
    >
        <x-slot:actions>
            <div class="flex flex-wrap gap-3">
                @can('update', $opportunity)
                    <a
                        class="btn-primary"
                        href="{{ route('opportunities.edit', $opportunity) }}"
                    >
                        Editar
                    </a>
                @endcan

                <a
                    class="btn-secondary"
                    href="{{ route('opportunities.index') }}"
                >
                    Voltar
                </a>
            </div>
        </x-slot:actions>
    </x-page-header>

    <nav
        class="mb-6 flex gap-1 overflow-x-auto border-b border-slate-200"
        aria-label="Seções da oportunidade"
    >
        <a
            href="#resumo"
            class="border-b-2 border-teal-700 px-4 py-3 text-sm font-semibold text-teal-800"
        >
            Resumo
        </a>

        <a
            href="#movimentacao"
            class="px-4 py-3 text-sm font-medium text-teal-700"
        >
            Pipeline
        </a>

        <a
            href="#historico"
            class="px-4 py-3 text-sm font-medium text-teal-700"
        >
            Histórico ({{ $histories->total() }})
        </a>
    </nav>

    <div class="grid gap-6 xl:grid-cols-3" id="resumo">
        <div class="space-y-6 xl:col-span-2">

            <section class="card">
                <h2 class="text-base font-semibold text-slate-900">
                    Dados principais
                </h2>

                <dl class="mt-4 grid gap-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Oportunidade
                        </dt>

                        <dd class="mt-1 font-semibold text-slate-900">
                            {{ $opportunity->title }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Valor
                        </dt>

                        <dd class="mt-1 text-lg font-bold text-slate-950">
                            {{ $opportunity->currency }}
                            {{
                                number_format(
                                    (float) $opportunity->amount,
                                    2,
                                    ',',
                                    '.'
                                )
                            }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Fechamento previsto
                        </dt>

                        <dd class="mt-1">
                            {{
                                $opportunity->expected_close_date?->format('d/m/Y')
                                ?? '—'
                            }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Criada em
                        </dt>

                        <dd class="mt-1">
                            {{ $opportunity->created_at->format('d/m/Y H:i') }}
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="card">
                <h2 class="text-base font-semibold text-slate-900">
                    Vínculos comerciais
                </h2>

                <dl class="mt-4 grid gap-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Lead
                        </dt>

                        <dd class="mt-1">
                            @if($opportunity->lead)
                                @can('view', $opportunity->lead)
                                    <a
                                        class="font-semibold text-teal-700 hover:text-teal-900"
                                        href="{{ route('leads.show', $opportunity->lead) }}"
                                    >
                                        {{
                                            $opportunity->lead->name
                                            ?: 'Lead #'.$opportunity->lead->id
                                        }}
                                    </a>
                                @else
                                    {{
                                        $opportunity->lead->name
                                        ?: 'Lead #'.$opportunity->lead->id
                                    }}
                                @endcan
                            @else
                                —
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Empresa
                        </dt>

                        <dd class="mt-1">
                            @if($opportunity->company)
                                @can('view', $opportunity->company)
                                    <a
                                        class="font-semibold text-teal-700 hover:text-teal-900"
                                        href="{{ route('companies.show', $opportunity->company) }}"
                                    >
                                        {{
                                            $opportunity->company->trade_name
                                            ?: $opportunity->company->legal_name
                                        }}
                                    </a>
                                @else
                                    {{
                                        $opportunity->company->trade_name
                                        ?: $opportunity->company->legal_name
                                    }}
                                @endcan
                            @else
                                —
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Contato
                        </dt>

                        <dd class="mt-1">
                            @if($opportunity->contact)
                                @can('view', $opportunity->contact)
                                    <a
                                        class="font-semibold text-teal-700 hover:text-teal-900"
                                        href="{{ route('contacts.show', $opportunity->contact) }}"
                                    >
                                        {{ $opportunity->contact->name }}
                                    </a>
                                @else
                                    {{ $opportunity->contact->name }}
                                @endcan
                            @else
                                —
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Responsável
                        </dt>

                        <dd class="mt-1">
                            {{
                                $opportunity->assignedUser?->name
                                ?? 'Não atribuído'
                            }}
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="card" id="movimentacao">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">
                            Movimentação no Pipeline
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Altere a etapa usando o fluxo controlado do CRM.
                        </p>
                    </div>

                    <span
                        class="rounded-full px-3 py-1 text-xs font-semibold {{ $stateClass }}"
                    >
                        {{ $stateLabel }}
                    </span>
                </div>

                <div class="mt-5 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase text-slate-500">
                            Pipeline
                        </p>

                        <p class="mt-1 font-semibold text-slate-900">
                            {{ $opportunity->pipeline?->name ?? '—' }}
                        </p>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase text-slate-500">
                            Etapa atual
                        </p>

                        <p class="mt-1 font-semibold text-slate-900">
                            {{ $opportunity->stage?->name ?? '—' }}
                        </p>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase text-slate-500">
                            Probabilidade
                        </p>

                        <p class="mt-1 text-lg font-bold text-slate-950">
                            {{ $opportunity->probability }}%
                        </p>
                    </div>
                </div>

                @can('update', $opportunity)
                    <form
                        class="mt-6 border-t border-slate-200 pt-6"
                        method="POST"
                        action="{{ route('opportunities.move-stage', $opportunity) }}"
                    >
                        @csrf
                        @method('PATCH')

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label class="label" for="stage_id">
                                    Nova etapa *
                                </label>

                                <select
                                    class="input"
                                    id="stage_id"
                                    name="stage_id"
                                    required
                                >
                                    @foreach($stages as $stage)
                                        <option
                                            value="{{ $stage->id }}"
                                            @selected(
                                                (string) old(
                                                    'stage_id',
                                                    $opportunity->stage_id
                                                ) === (string) $stage->id
                                            )
                                        >
                                            {{ $stage->name }}
                                            — {{ $stage->probability }}%
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="label" for="loss_reason_id">
                                    Motivo da perda
                                </label>

                                <select
                                    class="input"
                                    id="loss_reason_id"
                                    name="loss_reason_id"
                                >
                                    <option value="">Não aplicável</option>

                                    @foreach($lossReasons as $reason)
                                        <option
                                            value="{{ $reason->id }}"
                                            @selected(
                                                (string) old(
                                                    'loss_reason_id',
                                                    $opportunity->loss_reason_id
                                                ) === (string) $reason->id
                                            )
                                        >
                                            {{ $reason->name }}
                                        </option>
                                    @endforeach
                                </select>

                                <p class="mt-1 text-xs text-slate-500">
                                    Obrigatório ao mover para Perdido.
                                </p>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="label" for="notes">
                                    Observação da movimentação
                                </label>

                                <textarea
                                    class="input min-h-24"
                                    id="notes"
                                    name="notes"
                                    maxlength="10000"
                                    placeholder="Ex.: proposta apresentada, cliente solicitou revisão..."
                                >{{ old('notes') }}</textarea>
                            </div>
                        </div>

                        <div class="mt-5">
                            <button class="btn-primary" type="submit">
                                Atualizar etapa
                            </button>
                        </div>
                    </form>
                @endcan
            </section>

            <section class="card">
                <h2 class="text-base font-semibold text-slate-900">
                    Observações
                </h2>

                <p class="mt-4 whitespace-pre-line text-sm text-slate-700">{{ $opportunity->notes ?? 'Nenhuma observação registrada.' }}</p>
            </section>

            <section class="card" id="historico">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">
                            Histórico do Pipeline
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Registro de todas as movimentações da oportunidade.
                        </p>
                    </div>

                    <span
                        class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700"
                    >
                        {{ $histories->total() }} movimentações
                    </span>
                </div>

                <div class="mt-5 overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>De</th>
                                <th>Para</th>
                                <th>Responsável</th>
                                <th>Observação</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($histories as $history)
                                <tr>
                                    <td>
                                        {{
                                            $history->changed_at
                                                ->format('d/m/Y H:i')
                                        }}
                                    </td>

                                    <td>
                                        {{
                                            $history->fromStage?->name
                                            ?? 'Criação'
                                        }}
                                    </td>

                                    <td>
                                        <span class="font-semibold text-slate-900">
                                            {{ $history->toStage?->name ?? '—' }}
                                        </span>
                                    </td>

                                    <td>
                                        {{
                                            $history->changedByUser?->name
                                            ?? 'Sistema'
                                        }}
                                    </td>

                                    <td>
                                        {{ $history->notes ?? '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">
                                        Nenhuma movimentação registrada.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-5">
                    {{ $histories->links() }}
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="card">
                <h2 class="text-base font-semibold text-slate-900">
                    Comercial
                </h2>

                <dl class="mt-4 space-y-4">
                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Situação
                        </dt>

                        <dd class="mt-2">
                            <span
                                class="rounded-full px-2 py-1 text-xs font-semibold {{ $stateClass }}"
                            >
                                {{ $stateLabel }}
                            </span>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Etapa
                        </dt>

                        <dd class="mt-1">
                            {{ $opportunity->stage?->name ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Probabilidade
                        </dt>

                        <dd class="mt-1 text-xl font-bold text-slate-950">
                            {{ $opportunity->probability }}%
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Responsável
                        </dt>

                        <dd class="mt-1">
                            {{
                                $opportunity->assignedUser?->name
                                ?? 'Não atribuído'
                            }}
                        </dd>
                    </div>

                    @if($opportunity->won_at)
                        <div>
                            <dt class="text-xs font-semibold uppercase text-slate-500">
                                Ganho em
                            </dt>

                            <dd class="mt-1 text-emerald-700">
                                {{ $opportunity->won_at->format('d/m/Y H:i') }}
                            </dd>
                        </div>
                    @endif

                    @if($opportunity->lost_at)
                        <div>
                            <dt class="text-xs font-semibold uppercase text-slate-500">
                                Perdido em
                            </dt>

                            <dd class="mt-1 text-red-700">
                                {{ $opportunity->lost_at->format('d/m/Y H:i') }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase text-slate-500">
                                Motivo
                            </dt>

                            <dd class="mt-1">
                                {{ $opportunity->lossReason?->name ?? '—' }}
                            </dd>
                        </div>
                    @endif
                </dl>
            </section>

            @can('delete', $opportunity)
                <section class="rounded-xl border border-red-200 bg-white p-5">
                    <h2 class="font-semibold text-red-900">
                        Arquivar oportunidade
                    </h2>

                    <p class="mt-2 text-sm text-slate-600">
                        A oportunidade será arquivada por soft delete e não
                        será removida definitivamente.
                    </p>

                    <form
                        class="mt-4"
                        method="POST"
                        action="{{ route('opportunities.destroy', $opportunity) }}"
                        data-confirm="Arquivar esta oportunidade?"
                    >
                        @csrf
                        @method('DELETE')

                        <button
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700"
                            type="submit"
                        >
                            Arquivar
                        </button>
                    </form>
                </section>
            @endcan
        </aside>
    </div>
</x-layouts.app>
