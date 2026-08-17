@php
    $roles = ['decision_maker'=>'Decisor','influencer'=>'Influenciador','champion'=>'Patrocinador interno','user'=>'Usuário','technical'=>'Técnico','procurement'=>'Compras','financial'=>'Financeiro','gatekeeper'=>'Guardião','blocker'=>'Bloqueador','other'=>'Outro'];
    $levels = ['low'=>'Baixa','medium'=>'Média','high'=>'Alta','critical'=>'Crítica'];
@endphp
<x-errors />

<div class="space-y-4">
    <x-form-section title="Dados pessoais e profissionais" description="Identificação e vínculo profissional do contato.">
        <div class="form-grid form-grid-4">
            <div class="md:col-span-2"><label class="label" for="name">Nome *</label><input class="input" id="name" name="name" maxlength="255" required value="{{ old('name', $contact?->name) }}"></div>
            <div class="md:col-span-2"><label class="label" for="company_id">Empresa *</label><select class="input" id="company_id" name="company_id" required><option value="">Selecione</option>@foreach($companies as $company)<option value="{{ $company->id }}" @selected((string)old('company_id', $contact?->company_id ?? $selectedCompanyId ?? '') === (string)$company->id)>{{ $company->legal_name }}{{ $company->trashed() ? ' — Arquivada' : '' }}</option>@endforeach</select>@if($contact?->company?->trashed())<span class="mt-1 block text-xs font-medium text-amber-700">A empresa atual está arquivada. O vínculo histórico pode ser mantido.</span>@endif</div>
            <div class="md:col-span-2"><label class="label" for="job_title">Cargo</label><input class="input" id="job_title" name="job_title" maxlength="255" value="{{ old('job_title', $contact?->job_title) }}"></div>
            <div class="md:col-span-2"><label class="label" for="department">Departamento</label><input class="input" id="department" name="department" maxlength="255" value="{{ old('department', $contact?->department) }}"></div>
        </div>
    </x-form-section>

    <div class="grid gap-4 xl:grid-cols-2">
        <x-form-section title="Contato" description="Canais diretos e perfil profissional.">
            <div class="form-grid">
                <div><label class="label" for="email">E-mail</label><input class="input" id="email" name="email" type="email" value="{{ old('email', $contact?->email) }}"></div>
                <div><label class="label" for="phone">Telefone</label><input class="input" id="phone" name="phone" value="{{ old('phone', $contact?->phone) }}"></div>
                <div><label class="label" for="whatsapp">WhatsApp</label><input class="input" id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $contact?->whatsapp) }}"></div>
                <div><label class="label" for="linkedin_url">LinkedIn</label><input class="input" id="linkedin_url" name="linkedin_url" type="url" value="{{ old('linkedin_url', $contact?->linkedin_url) }}"></div>
            </div>
        </x-form-section>

        <x-form-section title="Papel comercial" description="Participação e situação no relacionamento.">
            <div class="form-grid">
                <div><label class="label" for="decision_role">Papel na decisão</label><select class="input" id="decision_role" name="decision_role"><option value="">Não definido</option>@foreach($roles as $value=>$label)<option value="{{ $value }}" @selected(old('decision_role', $contact?->decision_role)===$value)>{{ $label }}</option>@endforeach</select></div>
                <div><label class="label" for="influence_level">Influência</label><select class="input" id="influence_level" name="influence_level"><option value="">Não definida</option>@foreach($levels as $value=>$label)<option value="{{ $value }}" @selected(old('influence_level', $contact?->influence_level)===$value)>{{ $label }}</option>@endforeach</select></div>
                <input type="hidden" name="is_primary" value="0"><label class="choice-card" for="is_primary"><input id="is_primary" name="is_primary" type="checkbox" value="1" @checked(old('is_primary', $contact?->is_primary ?? false))><span><strong>Contato principal</strong><small>Referência principal desta empresa.</small></span></label>
                <input type="hidden" name="active" value="0"><label class="choice-card" for="active"><input id="active" name="active" type="checkbox" value="1" @checked(old('active', $contact?->active ?? true))><span><strong>Contato ativo</strong><small>Disponível para ações comerciais.</small></span></label>
            </div>
        </x-form-section>
    </div>

    <x-form-section title="Observações" description="Contexto adicional relevante para o relacionamento.">
        <label class="sr-only" for="notes">Observações</label><textarea class="input min-h-24" id="notes" name="notes" rows="5" maxlength="10000">{{ old('notes', $contact?->notes) }}</textarea>
    </x-form-section>

    <div class="form-actions"><a class="btn-secondary" href="{{ $contact ? route('contacts.show',$contact) : route('contacts.index') }}">Cancelar</a><button class="btn-primary" type="submit">Salvar contato</button></div>
</div>
