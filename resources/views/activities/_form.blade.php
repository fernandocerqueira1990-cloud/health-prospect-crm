<x-errors />

<div class="space-y-4">
    <x-form-section title="Dados principais" description="Identificação e classificação da interação comercial.">
        <div class="form-grid form-grid-4">
            <div class="sm:col-span-2 lg:col-span-2">
                <label class="label" for="subject">Assunto *</label>
                <input class="input" id="subject" name="subject" maxlength="255" required value="{{ old('subject', $activity?->subject) }}" placeholder="Ex.: Contato inicial sobre implantação do sistema">
            </div>
            <div>
                <label class="label" for="type">Tipo *</label>
                <select class="input" id="type" name="type" required>
                    <option value="">Selecione</option>
                    @foreach(['call' => 'Ligação', 'email' => 'E-mail', 'whatsapp' => 'WhatsApp', 'meeting' => 'Reunião', 'note' => 'Nota', 'other' => 'Outro'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('type', $activity?->type) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="direction">Direção</label>
                <select class="input" id="direction" name="direction">
                    <option value="">Não aplicável</option>
                    <option value="outbound" @selected(old('direction', $activity?->direction) === 'outbound')>Saída</option>
                    <option value="inbound" @selected(old('direction', $activity?->direction) === 'inbound')>Entrada</option>
                </select>
            </div>
        </div>
    </x-form-section>

    <div class="grid gap-4 xl:grid-cols-3">
        <x-form-section class="xl:col-span-2" title="Vínculo comercial" description="A atividade deve estar vinculada a pelo menos uma entidade.">
            <div class="form-grid">
                <div><label class="label" for="company_id">Empresa</label><select class="input" id="company_id" name="company_id"><option value="">Não vinculada</option>@foreach($companies as $company)<option value="{{ $company->id }}" @selected((string) old('company_id', $activity?->company_id) === (string) $company->id)>{{ $company->trade_name ?: $company->legal_name }}{{ $company->deleted_at ? ' — Arquivada' : '' }}</option>@endforeach</select></div>
                <div><label class="label" for="contact_id">Contato</label><select class="input" id="contact_id" name="contact_id"><option value="">Não vinculado</option>@foreach($contacts as $contact)<option value="{{ $contact->id }}" @selected((string) old('contact_id', $activity?->contact_id) === (string) $contact->id)>{{ $contact->name }}{{ $contact->job_title ? ' — '.$contact->job_title : '' }}{{ $contact->deleted_at ? ' — Arquivado' : '' }}</option>@endforeach</select></div>
                <div><label class="label" for="lead_id">Lead</label><select class="input" id="lead_id" name="lead_id"><option value="">Não vinculado</option>@foreach($leads as $lead)<option value="{{ $lead->id }}" @selected((string) old('lead_id', $activity?->lead_id) === (string) $lead->id)>{{ $lead->name ?: 'Lead #'.$lead->id }}{{ $lead->company_name ? ' — '.$lead->company_name : '' }}{{ $lead->deleted_at ? ' — Arquivado' : '' }}</option>@endforeach</select></div>
                <div><label class="label" for="opportunity_id">Oportunidade</label><select class="input" id="opportunity_id" name="opportunity_id"><option value="">Não vinculada</option>@foreach($opportunities as $opportunity)<option value="{{ $opportunity->id }}" @selected((string) old('opportunity_id', $activity?->opportunity_id) === (string) $opportunity->id)>{{ $opportunity->title }}{{ $opportunity->deleted_at ? ' — Arquivada' : '' }}</option>@endforeach</select></div>
            </div>
        </x-form-section>

        <div class="space-y-4">
            <x-form-section title="Agendamento" description="Quando e por quanto tempo ocorreu.">
                <div class="form-grid xl:grid-cols-1">
                    <div><label class="label" for="occurred_at">Data e hora *</label><input class="input" id="occurred_at" name="occurred_at" type="datetime-local" required value="{{ old('occurred_at', $activity?->occurred_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')) }}"></div>
                    <div><label class="label" for="duration_minutes">Duração em minutos</label><input class="input" id="duration_minutes" name="duration_minutes" type="number" min="1" max="10080" value="{{ old('duration_minutes', $activity?->duration_minutes) }}" placeholder="Ex.: 30"></div>
                </div>
            </x-form-section>
            <x-form-section title="Responsabilidade">
                <label class="label" for="assigned_user_id">Responsável</label>
                <select class="input" id="assigned_user_id" name="assigned_user_id"><option value="">Usuário atual</option>@foreach($assignedUsers as $user)<option value="{{ $user->id }}" @selected((string) old('assigned_user_id', $activity?->assigned_user_id) === (string) $user->id)>{{ $user->name }}{{ $user->active ? '' : ' — Inativo' }}</option>@endforeach</select>
            </x-form-section>
        </div>
    </div>

    <x-form-section title="Resultado / observações" description="Contexto da interação e resultado obtido.">
        <div class="form-grid">
            <div><label class="label" for="description">Descrição</label><textarea class="input min-h-24" id="description" name="description" maxlength="10000" placeholder="Detalhes da conversa, necessidades, contexto...">{{ old('description', $activity?->description) }}</textarea></div>
            <div><label class="label" for="outcome">Resultado</label><textarea class="input min-h-24" id="outcome" name="outcome" maxlength="2000" placeholder="Ex.: Cliente solicitou demonstração para próxima semana.">{{ old('outcome', $activity?->outcome) }}</textarea></div>
        </div>
    </x-form-section>

    <div class="form-actions">
        <a class="btn-secondary" href="{{ $activity ? route('activities.show', $activity) : route('activities.index') }}">Cancelar</a>
        <button class="btn-primary" type="submit">{{ $activity ? 'Salvar alterações' : 'Salvar' }}</button>
    </div>
</div>
