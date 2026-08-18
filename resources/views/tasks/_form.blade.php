<x-errors />

<div class="space-y-4">
    <x-form-section title="Dados principais" description="Identificação e classificação da pendência.">
        <div class="form-grid form-grid-4">
            <div class="sm:col-span-2 lg:col-span-2"><label class="label" for="title">Título *</label><input class="input" id="title" name="title" maxlength="255" required value="{{ old('title', $task?->title) }}" placeholder="Ex.: Retornar contato com a clínica"></div>
            <div><label class="label" for="status">Status *</label><select class="input" id="status" name="status" required>@foreach(['pending' => 'Pendente', 'in_progress' => 'Em andamento', 'completed' => 'Concluída', 'cancelled' => 'Cancelada'] as $value => $label)<option value="{{ $value }}" @selected(old('status', $task?->status ?? 'pending') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div><label class="label" for="priority">Prioridade *</label><select class="input" id="priority" name="priority" required>@foreach(['low' => 'Baixa', 'medium' => 'Média', 'high' => 'Alta', 'urgent' => 'Urgente'] as $value => $label)<option value="{{ $value }}" @selected(old('priority', $task?->priority ?? 'medium') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="sm:col-span-2 lg:col-span-4"><label class="label" for="description">Descrição</label><textarea class="input min-h-24" id="description" name="description" maxlength="10000" placeholder="Detalhes da tarefa, contexto e próximos passos...">{{ old('description', $task?->description) }}</textarea></div>
        </div>
    </x-form-section>

    <div class="grid gap-4 xl:grid-cols-3">
        <x-form-section class="xl:col-span-2" title="Vínculo comercial" description="Opcional. A tarefa também pode ser uma pendência interna.">
            <div class="form-grid">
                <div><label class="label" for="company_id">Empresa</label><select class="input" id="company_id" name="company_id"><option value="">Não vinculada</option>@foreach($companies as $company)<option value="{{ $company->id }}" @selected((string) old('company_id', $task?->company_id) === (string) $company->id)>{{ $company->trade_name ?: $company->legal_name }}{{ $company->deleted_at ? ' — Arquivada' : '' }}</option>@endforeach</select></div>
                <div><label class="label" for="contact_id">Contato</label><select class="input" id="contact_id" name="contact_id"><option value="">Não vinculado</option>@foreach($contacts as $contact)<option value="{{ $contact->id }}" @selected((string) old('contact_id', $task?->contact_id) === (string) $contact->id)>{{ $contact->name }}{{ $contact->job_title ? ' — '.$contact->job_title : '' }}</option>@endforeach</select></div>
                <div><label class="label" for="lead_id">Lead</label><select class="input" id="lead_id" name="lead_id"><option value="">Não vinculado</option>@foreach($leads as $lead)<option value="{{ $lead->id }}" @selected((string) old('lead_id', $task?->lead_id) === (string) $lead->id)>{{ $lead->name ?: 'Lead #'.$lead->id }}{{ $lead->company_name ? ' — '.$lead->company_name : '' }}</option>@endforeach</select></div>
                <div><label class="label" for="opportunity_id">Oportunidade</label><select class="input" id="opportunity_id" name="opportunity_id"><option value="">Não vinculada</option>@foreach($opportunities as $opportunity)<option value="{{ $opportunity->id }}" @selected((string) old('opportunity_id', $task?->opportunity_id) === (string) $opportunity->id)>{{ $opportunity->title }}</option>@endforeach</select></div>
            </div>
        </x-form-section>

        <div class="space-y-4">
            <x-form-section title="Planejamento" description="Prazo e pessoa responsável.">
                <div class="form-grid xl:grid-cols-1">
                    <div><label class="label" for="due_at">Prazo</label><input class="input" id="due_at" name="due_at" type="datetime-local" value="{{ old('due_at', $task?->due_at?->format('Y-m-d\TH:i')) }}"></div>
                    <div><label class="label" for="assigned_user_id">Responsável</label><select class="input" id="assigned_user_id" name="assigned_user_id"><option value="">Usuário atual</option>@foreach($assignedUsers as $user)<option value="{{ $user->id }}" @selected((string) old('assigned_user_id', $task?->assigned_user_id) === (string) $user->id)>{{ $user->name }}{{ $user->active ? '' : ' — Inativo' }}</option>@endforeach</select></div>
                </div>
            </x-form-section>
            <x-form-section title="Follow-up comercial" description="Configuração do próximo contato.">
                <label class="flex items-center gap-3"><input type="checkbox" name="is_follow_up" value="1" @checked(old('is_follow_up', $task?->is_follow_up))><span class="font-medium text-slate-900">Esta tarefa é um follow-up</span></label>
                <p class="mt-1 text-xs text-slate-500">Follow-ups exigem prazo, canal e vínculo comercial.</p>
                <div class="mt-3"><label class="label" for="follow_up_channel">Canal do follow-up</label><select class="input" id="follow_up_channel" name="follow_up_channel"><option value="">Selecione</option>@foreach(['call' => 'Ligação', 'email' => 'E-mail', 'whatsapp' => 'WhatsApp', 'meeting' => 'Reunião'] as $value => $label)<option value="{{ $value }}" @selected(old('follow_up_channel', $task?->follow_up_channel) === $value)>{{ $label }}</option>@endforeach</select></div>
            </x-form-section>
        </div>
    </div>

    <div class="form-actions">
        <a class="btn-secondary" href="{{ $task ? route('tasks.show', $task) : route('tasks.index') }}">Cancelar</a>
        <button class="btn-primary" type="submit">{{ $task ? 'Salvar alterações' : 'Salvar' }}</button>
    </div>
</div>
