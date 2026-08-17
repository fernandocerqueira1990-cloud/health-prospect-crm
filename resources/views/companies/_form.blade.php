<x-errors />
@php($priorityLabels = ['low' => 'Baixa', 'medium' => 'Média', 'high' => 'Alta', 'critical' => 'Crítica'])

<section>
    <h2 class="text-base font-semibold text-slate-900">Dados principais</h2>
    <div class="mt-4 grid gap-5 sm:grid-cols-2">
        <div><label class="label" for="legal_name">Razão social *</label><input class="input" id="legal_name" name="legal_name" maxlength="255" required value="{{ old('legal_name', $company?->legal_name) }}"></div>
        <div><label class="label" for="trade_name">Nome fantasia</label><input class="input" id="trade_name" name="trade_name" maxlength="255" value="{{ old('trade_name', $company?->trade_name) }}"></div>
        <div><label class="label" for="tax_id_country">País do documento fiscal</label><input class="input" id="tax_id_country" name="tax_id_country" maxlength="2" list="tax-id-countries" value="{{ old('tax_id_country', $company ? $company->tax_id_country : 'BR') }}" placeholder="BR"><datalist id="tax-id-countries"><option value="BR">Brasil</option><option value="CL">Chile</option><option value="AR">Argentina</option><option value="US">Estados Unidos</option><option value="MX">México</option></datalist><p class="mt-1 text-xs text-slate-500">Código ISO de duas letras. Obrigatório quando houver documento.</p></div>
        <div><label class="label" for="tax_id">CNPJ / Identificador fiscal</label><input class="input" id="tax_id" name="tax_id" maxlength="64" value="{{ old('tax_id', $company?->formattedTaxId()) }}" placeholder="CNPJ ou Tax ID internacional"></div>
        <div><label class="label" for="website">Website</label><input class="input" id="website" name="website" maxlength="2048" value="{{ old('website', $company?->website) }}" placeholder="https://exemplo.com"></div>
        <div><label class="label" for="segment">Segmento</label><input class="input" id="segment" name="segment" maxlength="255" value="{{ old('segment', $company?->segment) }}"></div>
        <div><label class="label" for="category">Categoria</label><input class="input" id="category" name="category" maxlength="255" value="{{ old('category', $company?->category) }}"></div>
    </div>
</section>

<section class="mt-8 border-t border-slate-200 pt-7">
    <h2 class="text-base font-semibold text-slate-900">Contato</h2>
    <div class="mt-4 grid gap-5 sm:grid-cols-2">
        <div><label class="label" for="email">E-mail</label><input class="input" id="email" name="email" type="email" maxlength="255" value="{{ old('email', $company?->email) }}"></div>
        <div><label class="label" for="phone">Telefone</label><input class="input" id="phone" name="phone" maxlength="64" value="{{ old('phone', $company?->phone) }}"></div>
    </div>
</section>

<section class="mt-8 border-t border-slate-200 pt-7">
    <h2 class="text-base font-semibold text-slate-900">Endereço</h2>
    <div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <div class="sm:col-span-2"><label class="label" for="street">Logradouro</label><input class="input" id="street" name="street" maxlength="255" value="{{ old('street', $company?->street) }}"></div>
        <div><label class="label" for="number">Número</label><input class="input" id="number" name="number" maxlength="32" value="{{ old('number', $company?->number) }}"></div>
        <div><label class="label" for="complement">Complemento</label><input class="input" id="complement" name="complement" maxlength="255" value="{{ old('complement', $company?->complement) }}"></div>
        <div><label class="label" for="district">Bairro</label><input class="input" id="district" name="district" maxlength="255" value="{{ old('district', $company?->district) }}"></div>
        <div><label class="label" for="city">Cidade</label><input class="input" id="city" name="city" maxlength="255" value="{{ old('city', $company?->city) }}"></div>
        <div><label class="label" for="state">Estado / UF</label><input class="input" id="state" name="state" maxlength="100" value="{{ old('state', $company?->state) }}"></div>
        <div><label class="label" for="postal_code">CEP / Código postal</label><input class="input" id="postal_code" name="postal_code" maxlength="32" value="{{ old('postal_code', $company?->postal_code) }}"></div>
    </div>
</section>

<section class="mt-8 border-t border-slate-200 pt-7">
    <h2 class="text-base font-semibold text-slate-900">Comercial</h2>
    <div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <div><label class="label" for="assigned_user_id">Responsável</label><select class="input" id="assigned_user_id" name="assigned_user_id"><option value="">Não atribuído</option>@foreach($assignedUsers as $assignedUser)<option value="{{ $assignedUser->id }}" @selected((string) old('assigned_user_id', $company?->assigned_user_id) === (string) $assignedUser->id)>{{ $assignedUser->name }}{{ $assignedUser->active ? '' : ' — Inativo' }}</option>@endforeach</select></div>
        <div><label class="label" for="priority">Prioridade</label><select class="input" id="priority" name="priority"><option value="">Não definida</option>@foreach($priorityLabels as $value => $label)<option value="{{ $value }}" @selected(old('priority', $company?->priority) === $value)>{{ $label }}</option>@endforeach</select></div>
        <div><label class="label" for="employee_count_estimate">Estimativa de funcionários</label><input class="input" id="employee_count_estimate" name="employee_count_estimate" type="number" min="0" value="{{ old('employee_count_estimate', $company?->employee_count_estimate) }}"></div>
    </div>
</section>

<section class="mt-8 border-t border-slate-200 pt-7">
    <h2 class="text-base font-semibold text-slate-900">Observações</h2>
    <div class="mt-4"><label class="sr-only" for="notes">Observações</label><textarea class="input min-h-32" id="notes" name="notes" maxlength="10000">{{ old('notes', $company?->notes) }}</textarea></div>
</section>

<div class="mt-8 flex flex-wrap gap-3"><button class="btn-primary" type="submit">Salvar empresa</button><a class="btn-secondary" href="{{ $company ? route('companies.show', $company) : route('companies.index') }}">Cancelar</a></div>
