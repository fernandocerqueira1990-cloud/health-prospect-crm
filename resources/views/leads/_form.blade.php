<x-errors />

@php
    $statusLabels = ['new' => 'Novo', 'contacted' => 'Contatado', 'qualified' => 'Qualificado', 'nurturing' => 'Nutrição', 'converted' => 'Convertido', 'disqualified' => 'Desqualificado'];
    $priorityLabels = ['low' => 'Baixa', 'medium' => 'Média', 'high' => 'Alta', 'critical' => 'Crítica'];
    $temperatureLabels = ['cold' => 'Frio', 'warm' => 'Morno', 'hot' => 'Quente'];
@endphp

<div class="space-y-4">
    <x-form-section title="Dados principais" description="Identificação, contato e vínculo comercial do lead.">
        <div class="form-grid form-grid-4">
            <div class="md:col-span-2">
                <label class="label" for="name">Nome</label>
                <input class="input" id="name" name="name" maxlength="255" value="{{ old('name', $lead?->name) }}" placeholder="Nome do potencial cliente">
            </div>
            <div class="md:col-span-2">
                <label class="label" for="company_name">Empresa informada</label>
                <input class="input" id="company_name" name="company_name" maxlength="255" value="{{ old('company_name', $lead?->company_name) }}" placeholder="Empresa ainda não cadastrada no CRM">
            </div>
            <div class="md:col-span-2 lg:col-span-1">
                <label class="label" for="job_title">Cargo</label>
                <input class="input" id="job_title" name="job_title" maxlength="255" value="{{ old('job_title', $lead?->job_title) }}" placeholder="Ex.: Gerente de TI">
            </div>
            <div class="md:col-span-2 lg:col-span-1">
                <label class="label" for="email">E-mail</label>
                <input class="input" id="email" name="email" type="email" maxlength="255" value="{{ old('email', $lead?->email) }}">
            </div>
            <div>
                <label class="label" for="phone">Telefone</label>
                <input class="input" id="phone" name="phone" maxlength="64" value="{{ old('phone', $lead?->phone) }}" placeholder="+5571...">
            </div>
            <div>
                <label class="label" for="whatsapp">WhatsApp</label>
                <input class="input" id="whatsapp" name="whatsapp" maxlength="64" value="{{ old('whatsapp', $lead?->whatsapp) }}" placeholder="+5571...">
            </div>
        </div>
    </x-form-section>

    <div class="grid gap-4 xl:grid-cols-2">
        <x-form-section title="Vínculo com o CRM" description="Opcional. Associe cadastros já existentes.">
            <div class="form-grid">
                <div>
                    <label class="label" for="company_id">Empresa</label>
                    <select class="input" id="company_id" name="company_id">
                        <option value="">Não vinculada</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" @selected((string) old('company_id', $lead?->company_id) === (string) $company->id)>{{ $company->trade_name ?: $company->legal_name }}{{ $company->deleted_at ? ' — Arquivada' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="contact_id">Contato</label>
                    <select class="input" id="contact_id" name="contact_id">
                        <option value="">Não vinculado</option>
                        @foreach($contacts as $contact)
                            <option value="{{ $contact->id }}" @selected((string) old('contact_id', $lead?->contact_id) === (string) $contact->id)>{{ $contact->name }}{{ $contact->job_title ? ' — '.$contact->job_title : '' }}{{ $contact->deleted_at ? ' — Arquivado' : '' }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Se selecionar um contato, selecione também a empresa correspondente.</p>
                </div>
            </div>
        </x-form-section>

        <x-form-section title="Origem" description="Aquisição e atribuição desta oportunidade.">
            <div class="form-grid form-grid-3">
                <div>
                    <label class="label" for="source_id">Origem *</label>
                    <select class="input" id="source_id" name="source_id" required>
                        <option value="">Selecione</option>
                        @foreach($sources as $source)
                            <option value="{{ $source->id }}" @selected((string) old('source_id', $lead?->source_id) === (string) $source->id)>{{ $source->name }}{{ $source->active ? '' : ' — Inativa' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="channel_id">Canal *</label>
                    <select class="input" id="channel_id" name="channel_id" required>
                        <option value="">Selecione</option>
                        @foreach($channels as $channel)
                            <option value="{{ $channel->id }}" @selected((string) old('channel_id', $lead?->channel_id) === (string) $channel->id)>{{ $channel->name }}{{ $channel->active ? '' : ' — Inativo' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2 lg:col-span-1">
                    <label class="label" for="assigned_user_id">Responsável</label>
                    <select class="input" id="assigned_user_id" name="assigned_user_id">
                        <option value="">Não atribuído</option>
                        @foreach($assignedUsers as $assignedUser)
                            <option value="{{ $assignedUser->id }}" @selected((string) old('assigned_user_id', $lead?->assigned_user_id) === (string) $assignedUser->id)>{{ $assignedUser->name }}{{ $assignedUser->active ? '' : ' — Inativo' }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </x-form-section>
    </div>

    <x-form-section title="Qualificação" description="Classificação comercial e score atual do lead.">
        <div class="form-grid form-grid-4">
            <div><label class="label" for="status">Status *</label><select class="input" id="status" name="status" required>@foreach($statusLabels as $value => $label)<option value="{{ $value }}" @selected(old('status', $lead?->status ?? 'new') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div><label class="label" for="priority">Prioridade</label><select class="input" id="priority" name="priority"><option value="">Não definida</option>@foreach($priorityLabels as $value => $label)<option value="{{ $value }}" @selected(old('priority', $lead?->priority) === $value)>{{ $label }}</option>@endforeach</select></div>
            <div><label class="label" for="temperature">Temperatura</label><select class="input" id="temperature" name="temperature"><option value="">Não definida</option>@foreach($temperatureLabels as $value => $label)<option value="{{ $value }}" @selected(old('temperature', $lead?->temperature) === $value)>{{ $label }}</option>@endforeach</select></div>
            <div><label class="label" for="score">Score *</label><div class="relative"><input class="input pr-12" id="score" name="score" type="number" min="0" max="100" required value="{{ old('score', $lead?->score ?? 0) }}"><span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400">/100</span></div><p class="mt-1 text-xs text-slate-500">Valor entre 0 e 100.</p></div>
        </div>
    </x-form-section>

    <x-form-section title="Acompanhamento" description="Marcos e próximos passos do relacionamento.">
        <div class="form-grid form-grid-3">
            <div><label class="label" for="last_interaction_at">Última interação</label><input class="input" id="last_interaction_at" name="last_interaction_at" type="datetime-local" value="{{ old('last_interaction_at', $lead?->last_interaction_at?->format('Y-m-d\TH:i')) }}"></div>
            <div><label class="label" for="next_action_at">Próxima ação</label><input class="input" id="next_action_at" name="next_action_at" type="datetime-local" value="{{ old('next_action_at', $lead?->next_action_at?->format('Y-m-d\TH:i')) }}"></div>
            <div><label class="label" for="qualified_at">Qualificado em</label><input class="input" id="qualified_at" name="qualified_at" type="datetime-local" value="{{ old('qualified_at', $lead?->qualified_at?->format('Y-m-d\TH:i')) }}"></div>
            <div><label class="label" for="converted_at">Convertido em</label><input class="input" id="converted_at" name="converted_at" type="datetime-local" value="{{ old('converted_at', $lead?->converted_at?->format('Y-m-d\TH:i')) }}"></div>
            <div><label class="label" for="lost_at">Desqualificado / perdido em</label><input class="input" id="lost_at" name="lost_at" type="datetime-local" value="{{ old('lost_at', $lead?->lost_at?->format('Y-m-d\TH:i')) }}"></div>
        </div>
    </x-form-section>

    <x-form-section title="Observações" description="Contexto comercial e necessidades identificadas.">
        <label class="sr-only" for="notes">Observações</label>
        <textarea class="input min-h-24" id="notes" name="notes" maxlength="10000" placeholder="Informações comerciais, contexto da prospecção, necessidades identificadas...">{{ old('notes', $lead?->notes) }}</textarea>
    </x-form-section>

    <div class="form-actions">
        <a class="btn-secondary" href="{{ $lead ? route('leads.show', $lead) : route('leads.index') }}">Cancelar</a>
        <button class="btn-primary" type="submit">{{ $lead ? 'Salvar alterações' : 'Criar lead' }}</button>
    </div>
</div>
