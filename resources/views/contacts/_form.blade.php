@php
    $roles = ['decision_maker'=>'Decisor','influencer'=>'Influenciador','champion'=>'Patrocinador interno','user'=>'Usuário','technical'=>'Técnico','procurement'=>'Compras','financial'=>'Financeiro','gatekeeper'=>'Guardião','blocker'=>'Bloqueador','other'=>'Outro'];
    $levels = ['low'=>'Baixa','medium'=>'Média','high'=>'Alta','critical'=>'Crítica'];
@endphp
<x-errors />
<div class="space-y-8">
    <section><h2 class="font-semibold text-slate-900">Dados pessoais</h2><div class="mt-4 grid gap-5 md:grid-cols-2">
        <label class="md:col-span-2"><span class="label">Nome *</span><input class="input" name="name" maxlength="255" required value="{{ old('name', $contact?->name) }}"></label>
        <label><span class="label">Empresa *</span><select class="input" name="company_id" required><option value="">Selecione</option>@foreach($companies as $company)<option value="{{ $company->id }}" @selected((string)old('company_id', $contact?->company_id ?? $selectedCompanyId ?? '') === (string)$company->id)>{{ $company->legal_name }}{{ $company->trashed() ? ' — Arquivada' : '' }}</option>@endforeach</select>@if($contact?->company?->trashed())<span class="mt-1 block text-xs font-medium text-amber-700">A empresa atual está arquivada. O vínculo histórico pode ser mantido.</span>@endif</label>
    </div></section>
    <section><h2 class="font-semibold text-slate-900">Dados profissionais</h2><div class="mt-4 grid gap-5 md:grid-cols-2">
        <label><span class="label">Cargo</span><input class="input" name="job_title" maxlength="255" value="{{ old('job_title', $contact?->job_title) }}"></label>
        <label><span class="label">Departamento</span><input class="input" name="department" maxlength="255" value="{{ old('department', $contact?->department) }}"></label>
    </div></section>
    <section><h2 class="font-semibold text-slate-900">Contato</h2><div class="mt-4 grid gap-5 md:grid-cols-2">
        <label><span class="label">E-mail</span><input class="input" name="email" type="email" value="{{ old('email', $contact?->email) }}"></label>
        <label><span class="label">Telefone</span><input class="input" name="phone" value="{{ old('phone', $contact?->phone) }}"></label>
        <label><span class="label">WhatsApp</span><input class="input" name="whatsapp" value="{{ old('whatsapp', $contact?->whatsapp) }}"></label>
        <label><span class="label">LinkedIn</span><input class="input" name="linkedin_url" type="url" value="{{ old('linkedin_url', $contact?->linkedin_url) }}"></label>
    </div></section>
    <section><h2 class="font-semibold text-slate-900">Relacionamento comercial</h2><div class="mt-4 grid gap-5 md:grid-cols-2">
        <label><span class="label">Papel na decisão</span><select class="input" name="decision_role"><option value="">Não definido</option>@foreach($roles as $value=>$label)<option value="{{ $value }}" @selected(old('decision_role', $contact?->decision_role)===$value)>{{ $label }}</option>@endforeach</select></label>
        <label><span class="label">Influência</span><select class="input" name="influence_level"><option value="">Não definida</option>@foreach($levels as $value=>$label)<option value="{{ $value }}" @selected(old('influence_level', $contact?->influence_level)===$value)>{{ $label }}</option>@endforeach</select></label>
        <input type="hidden" name="is_primary" value="0"><label class="flex items-center gap-2"><input name="is_primary" type="checkbox" value="1" @checked(old('is_primary', $contact?->is_primary ?? false))><span>Contato principal</span></label>
        <input type="hidden" name="active" value="0"><label class="flex items-center gap-2"><input name="active" type="checkbox" value="1" @checked(old('active', $contact?->active ?? true))><span>Contato ativo</span></label>
    </div></section>
    <section><h2 class="font-semibold text-slate-900">Observações</h2><textarea class="input mt-4" name="notes" rows="5" maxlength="10000">{{ old('notes', $contact?->notes) }}</textarea></section>
    <div class="flex gap-3"><button class="btn-primary" type="submit">Salvar contato</button><a class="btn-secondary" href="{{ $contact ? route('contacts.show',$contact) : route('contacts.index') }}">Cancelar</a></div>
</div>
