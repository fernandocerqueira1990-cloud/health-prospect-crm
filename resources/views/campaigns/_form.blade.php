<div class="space-y-4 p-4 sm:p-6">
    <x-errors />

    <x-form-section title="Visão geral" description="Identificação e situação atual da campanha.">
        <div class="form-grid">
            <div><label class="label" for="name">Nome *</label><input class="input" id="name" name="name" maxlength="255" required value="{{ old('name', $campaign?->name) }}" placeholder="Ex.: Jornada de Saúde 2026"></div>
            <div><label class="label" for="status">Status *</label><select class="input" id="status" name="status" required>@foreach(['draft' => 'Rascunho', 'planned' => 'Planejada', 'active' => 'Ativa', 'paused' => 'Pausada', 'completed' => 'Concluída', 'cancelled' => 'Cancelada'] as $value => $label)<option value="{{ $value }}" @selected(old('status', $campaign?->status ?? 'draft') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="md:col-span-2"><label class="label" for="description">Descrição</label><textarea class="input min-h-24" id="description" name="description" maxlength="10000" placeholder="Objetivo e contexto da campanha">{{ old('description', $campaign?->description) }}</textarea></div>
        </div>
    </x-form-section>

    <div class="grid gap-4 xl:grid-cols-2">
        <x-form-section title="Planejamento" description="Período e investimento previsto.">
            <div class="form-grid">
                <div><label class="label" for="start_date">Data início</label><input class="input" id="start_date" name="start_date" type="date" value="{{ old('start_date', $campaign?->start_date?->format('Y-m-d')) }}"></div>
                <div><label class="label" for="end_date">Data fim</label><input class="input" id="end_date" name="end_date" type="date" value="{{ old('end_date', $campaign?->end_date?->format('Y-m-d')) }}"></div>
                <div><label class="label" for="budget">Orçamento</label><input class="input" id="budget" name="budget" type="number" min="0" step="0.01" value="{{ old('budget', $campaign?->budget) }}" placeholder="0,00"></div>
                <div><label class="label" for="currency">Moeda *</label><input class="input uppercase" id="currency" name="currency" minlength="3" maxlength="3" pattern="[A-Z]{3}" required value="{{ old('currency', $campaign?->currency ?? 'BRL') }}"></div>
            </div>
        </x-form-section>

        <x-form-section title="Responsável / Canal" description="Atribuição operacional da campanha.">
            <div class="form-grid xl:grid-cols-1">
                <div><label class="label" for="channel_id">Canal</label><select class="input" id="channel_id" name="channel_id"><option value="">Não definido</option>@foreach($channels as $channel)<option value="{{ $channel->id }}" @selected((string) old('channel_id', $campaign?->channel_id) === (string) $channel->id)>{{ $channel->name }}{{ $channel->active ? '' : ' — Inativo' }}</option>@endforeach</select></div>
                <div><label class="label" for="owner_user_id">Responsável</label><select class="input" id="owner_user_id" name="owner_user_id"><option value="">Não atribuído</option>@foreach($owners as $owner)<option value="{{ $owner->id }}" @selected((string) old('owner_user_id', $campaign?->owner_user_id) === (string) $owner->id)>{{ $owner->name }}{{ $owner->active ? '' : ' — Inativo' }}</option>@endforeach</select></div>
            </div>
        </x-form-section>
    </div>

    <x-form-section title="Aquisição / UTMs" description="Parâmetros usados para identificar a origem do tráfego.">
        <div class="form-grid lg:grid-cols-3">
            <div><label class="label" for="utm_source">UTM Source</label><input class="input" id="utm_source" name="utm_source" maxlength="255" value="{{ old('utm_source', $campaign?->utm_source) }}"></div>
            <div><label class="label" for="utm_medium">UTM Medium</label><input class="input" id="utm_medium" name="utm_medium" maxlength="255" value="{{ old('utm_medium', $campaign?->utm_medium) }}"></div>
            <div><label class="label" for="utm_campaign">UTM Campaign</label><input class="input" id="utm_campaign" name="utm_campaign" maxlength="255" value="{{ old('utm_campaign', $campaign?->utm_campaign) }}"></div>
            <div><label class="label" for="utm_content">UTM Content</label><input class="input" id="utm_content" name="utm_content" maxlength="255" value="{{ old('utm_content', $campaign?->utm_content) }}"></div>
            <div><label class="label" for="utm_term">UTM Term</label><input class="input" id="utm_term" name="utm_term" maxlength="255" value="{{ old('utm_term', $campaign?->utm_term) }}"></div>
        </div>
    </x-form-section>

    <x-form-section title="Observações"><label class="label" for="notes">Observações</label><textarea class="input min-h-24" id="notes" name="notes" maxlength="10000" placeholder="Informações internas relevantes">{{ old('notes', $campaign?->notes) }}</textarea></x-form-section>

    <div class="form-actions"><a class="btn-secondary" href="{{ $campaign ? route('campaigns.show', $campaign) : route('campaigns.index') }}">Cancelar</a><button class="btn-primary" type="submit">{{ $campaign ? 'Salvar alterações' : 'Salvar' }}</button></div>
</div>
