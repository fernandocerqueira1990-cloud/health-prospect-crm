<x-errors />

<section>
    <h2 class="text-base font-semibold text-slate-900">
        Dados da oportunidade
    </h2>

    <p class="mt-1 text-sm text-slate-500">
        Registre o negócio, valor estimado e vínculos comerciais.
    </p>

    <div class="mt-4 grid gap-5 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label class="label" for="title">Título *</label>

            <input
                class="input"
                id="title"
                name="title"
                maxlength="255"
                required
                value="{{ old('title', $opportunity?->title) }}"
                placeholder="Ex.: Implantação ERP Clínica Horizonte"
            >
        </div>

        <div>
            <label class="label" for="lead_id">Lead</label>

            <select class="input" id="lead_id" name="lead_id">
                <option value="">Não vinculado</option>

                @foreach($leads as $lead)
                    <option
                        value="{{ $lead->id }}"
                        @selected(
                            (string) old('lead_id', $opportunity?->lead_id)
                            === (string) $lead->id
                        )
                    >
                        {{ $lead->name ?: 'Lead #'.$lead->id }}
                        @if($lead->company_name)
                            — {{ $lead->company_name }}
                        @endif
                        {{ $lead->deleted_at ? ' — Arquivado' : '' }}
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
                <option value="">Não atribuído</option>

                @foreach($assignedUsers as $user)
                    <option
                        value="{{ $user->id }}"
                        @selected(
                            (string) old(
                                'assigned_user_id',
                                $opportunity?->assigned_user_id
                            ) === (string) $user->id
                        )
                    >
                        {{ $user->name }}
                        {{ $user->active ? '' : ' — Inativo' }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</section>

<section class="mt-8 border-t border-slate-200 pt-7">
    <h2 class="text-base font-semibold text-slate-900">
        Empresa e contato
    </h2>

    <div class="mt-4 grid gap-5 sm:grid-cols-2">
        <div>
            <label class="label" for="company_id">Empresa</label>

            <select class="input" id="company_id" name="company_id">
                <option value="">Não vinculada</option>

                @foreach($companies as $company)
                    <option
                        value="{{ $company->id }}"
                        @selected(
                            (string) old(
                                'company_id',
                                $opportunity?->company_id
                            ) === (string) $company->id
                        )
                    >
                        {{ $company->trade_name ?: $company->legal_name }}
                        {{ $company->deleted_at ? ' — Arquivada' : '' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="label" for="contact_id">Contato</label>

            <select class="input" id="contact_id" name="contact_id">
                <option value="">Não vinculado</option>

                @foreach($contacts as $contact)
                    <option
                        value="{{ $contact->id }}"
                        @selected(
                            (string) old(
                                'contact_id',
                                $opportunity?->contact_id
                            ) === (string) $contact->id
                        )
                    >
                        {{ $contact->name }}
                        {{ $contact->job_title ? ' — '.$contact->job_title : '' }}
                        {{ $contact->deleted_at ? ' — Arquivado' : '' }}
                    </option>
                @endforeach
            </select>

            <p class="mt-1 text-xs text-slate-500">
                O contato deve pertencer à empresa selecionada.
            </p>
        </div>
    </div>
</section>

@if($opportunity === null)
    <section class="mt-8 border-t border-slate-200 pt-7">
        <h2 class="text-base font-semibold text-slate-900">
            Pipeline
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Defina em qual funil e etapa a oportunidade iniciará.
        </p>

        <div class="mt-4 grid gap-5 sm:grid-cols-2">
            <div>
                <label class="label" for="pipeline_id">Pipeline *</label>

                <select
                    class="input"
                    id="pipeline_id"
                    name="pipeline_id"
                    required
                >
                    <option value="">Selecione</option>

                    @foreach($pipelines as $pipeline)
                        <option
                            value="{{ $pipeline->id }}"
                            @selected(
                                (string) old('pipeline_id')
                                === (string) $pipeline->id
                            )
                        >
                            {{ $pipeline->name }}
                            {{ $pipeline->is_default ? ' — Padrão' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="label" for="stage_id">Etapa inicial *</label>

                <select
                    class="input"
                    id="stage_id"
                    name="stage_id"
                    required
                >
                    <option value="">Selecione</option>

                    @foreach($stages as $stage)
                        <option
                            value="{{ $stage->id }}"
                            @selected(
                                (string) old('stage_id')
                                === (string) $stage->id
                            )
                        >
                            {{ $stage->name }}
                            — {{ $stage->probability }}%
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-2">
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
                                (string) old('loss_reason_id')
                                === (string) $reason->id
                            )
                        >
                            {{ $reason->name }}
                        </option>
                    @endforeach
                </select>

                <p class="mt-1 text-xs text-slate-500">
                    Obrigatório somente se a etapa inicial for Perdido.
                </p>
            </div>
        </div>
    </section>
@else
    <section class="mt-8 border-t border-slate-200 pt-7">
        <h2 class="text-base font-semibold text-slate-900">
            Pipeline atual
        </h2>

        <div class="mt-4 grid gap-4 sm:grid-cols-2">
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
                    Etapa
                </p>

                <p class="mt-1 font-semibold text-slate-900">
                    {{ $opportunity->stage?->name ?? '—' }}
                </p>
            </div>
        </div>

        <p class="mt-3 text-xs text-slate-500">
            A etapa não é alterada nesta tela. Utilize o fluxo do Pipeline
            para preservar o histórico da oportunidade.
        </p>
    </section>
@endif

<section class="mt-8 border-t border-slate-200 pt-7">
    <h2 class="text-base font-semibold text-slate-900">
        Valor e previsão
    </h2>

    <div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <div>
            <label class="label" for="amount">Valor *</label>

            <input
                class="input"
                id="amount"
                name="amount"
                type="number"
                min="0"
                step="0.01"
                required
                value="{{ old('amount', $opportunity?->amount ?? '0.00') }}"
            >
        </div>

        <div>
            <label class="label" for="currency">Moeda *</label>

            <select class="input" id="currency" name="currency" required>
                @foreach([
                    'BRL' => 'BRL — Real',
                    'USD' => 'USD — Dólar',
                    'EUR' => 'EUR — Euro',
                    'CLP' => 'CLP — Peso chileno',
                ] as $value => $label)
                    <option
                        value="{{ $value }}"
                        @selected(
                            old(
                                'currency',
                                $opportunity?->currency ?? 'BRL'
                            ) === $value
                        )
                    >
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="label" for="expected_close_date">
                Fechamento previsto
            </label>

            <input
                class="input"
                id="expected_close_date"
                name="expected_close_date"
                type="date"
                value="{{
                    old(
                        'expected_close_date',
                        $opportunity?->expected_close_date?->format('Y-m-d')
                    )
                }}"
            >
        </div>
    </div>
</section>

<section class="mt-8 border-t border-slate-200 pt-7">
    <h2 class="text-base font-semibold text-slate-900">
        Observações
    </h2>

    <div class="mt-4">
        <textarea
            class="input min-h-32"
            id="notes"
            name="notes"
            maxlength="10000"
            placeholder="Necessidades, contexto comercial, escopo, objeções..."
        >{{ old('notes', $opportunity?->notes) }}</textarea>
    </div>
</section>

<div class="mt-8 flex flex-wrap gap-3">
    <button class="btn-primary" type="submit">
        {{ $opportunity ? 'Salvar alterações' : 'Criar oportunidade' }}
    </button>

    <a
        class="btn-secondary"
        href="{{
            $opportunity
                ? route('opportunities.show', $opportunity)
                : route('opportunities.index')
        }}"
    >
        Cancelar
    </a>
</div>
