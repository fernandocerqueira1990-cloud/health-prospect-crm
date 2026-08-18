@php
    $statusLabels = ['draft' => 'Rascunho', 'planned' => 'Planejada', 'active' => 'Ativa', 'paused' => 'Pausada', 'completed' => 'Concluída', 'cancelled' => 'Cancelada'];
    $statusVariants = ['draft' => 'neutral', 'planned' => 'info', 'active' => 'success', 'paused' => 'warning', 'completed' => 'success', 'cancelled' => 'danger'];
    $leadStatusLabels = ['new' => 'Novo', 'contacted' => 'Contatado', 'qualified' => 'Qualificado', 'nurturing' => 'Nutrição', 'converted' => 'Convertido', 'disqualified' => 'Desqualificado'];
    $leadStatusVariants = ['new' => 'info', 'contacted' => 'info', 'qualified' => 'success', 'nurturing' => 'info', 'converted' => 'success', 'disqualified' => 'neutral'];
@endphp
<x-layouts.app title="{{ $campaign->name }}">
    <x-page-header :title="$campaign->name" description="Detalhes e planejamento da campanha."><x-slot:actions>@can('update', $campaign)<a class="btn-primary" href="{{ route('campaigns.edit', $campaign) }}">Editar</a>@endcan<a class="btn-secondary" href="{{ route('campaigns.index') }}">Voltar</a></x-slot:actions></x-page-header>
    <div class="grid gap-4 xl:grid-cols-3"><div class="space-y-4 xl:col-span-2">
        <x-form-section title="Visão geral"><dl class="detail-grid"><div class="detail-item"><dt class="detail-label">Nome</dt><dd class="detail-value break-words">{{ $campaign->name }}</dd></div><div class="detail-item"><dt class="detail-label">Status</dt><dd class="detail-value"><x-status-badge :variant="$statusVariants[$campaign->status] ?? 'neutral'">{{ $statusLabels[$campaign->status] ?? $campaign->status }}</x-status-badge></dd></div><div class="detail-item md:col-span-2"><dt class="detail-label">Descrição</dt><dd class="detail-value whitespace-pre-line break-words">{{ $campaign->description ?? 'Nenhuma descrição registrada.' }}</dd></div></dl></x-form-section>
        <x-form-section title="Planejamento"><dl class="detail-grid lg:grid-cols-3"><div class="detail-item"><dt class="detail-label">Início</dt><dd class="detail-value">{{ $campaign->start_date?->format('d/m/Y') ?? '—' }}</dd></div><div class="detail-item"><dt class="detail-label">Fim</dt><dd class="detail-value">{{ $campaign->end_date?->format('d/m/Y') ?? '—' }}</dd></div><div class="detail-item"><dt class="detail-label">Orçamento</dt><dd class="detail-value">{{ $campaign->budget !== null ? $campaign->currency.' '.number_format((float) $campaign->budget, 2, ',', '.') : '—' }}</dd></div></dl></x-form-section>
        <x-form-section title="Aquisição / UTMs"><dl class="detail-grid lg:grid-cols-3">@foreach(['utm_source' => 'UTM Source', 'utm_medium' => 'UTM Medium', 'utm_campaign' => 'UTM Campaign', 'utm_content' => 'UTM Content', 'utm_term' => 'UTM Term'] as $field => $label)<div class="detail-item"><dt class="detail-label">{{ $label }}</dt><dd class="detail-value break-words">{{ $campaign->{$field} ?? '—' }}</dd></div>@endforeach</dl></x-form-section>
        <x-form-section title="Observações"><p class="whitespace-pre-line break-words text-sm leading-6 text-slate-700">{{ $campaign->notes ?? 'Nenhuma observação registrada.' }}</p></x-form-section>
        <x-form-section title="Leads da campanha" description="Leads com ao menos um touch atribuído a esta campanha.">
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Lead</th><th>Status</th><th>Responsável</th><th>Data do vínculo/touch</th></tr></thead>
                    <tbody>
                        @forelse($leads as $lead)
                            <tr>
                                <td>
                                    @can('view', $lead)<a class="table-primary-link" href="{{ route('leads.show', $lead) }}">{{ $lead->name ?: 'Lead #'.$lead->id }}</a>@else<span class="font-medium">{{ $lead->name ?: 'Lead #'.$lead->id }}</span>@endcan
                                    <span class="table-secondary-line">{{ $lead->company?->trade_name ?: ($lead->company?->legal_name ?: ($lead->company_name ?: 'Empresa não informada')) }}</span>
                                </td>
                                <td><x-status-badge :variant="$leadStatusVariants[$lead->status] ?? 'neutral'">{{ $leadStatusLabels[$lead->status] ?? $lead->status }}</x-status-badge></td>
                                <td>{{ $lead->assignedUser?->name ?? 'Não atribuído' }}</td>
                                <td class="whitespace-nowrap">{{ $lead->campaign_touched_at ? \Illuminate\Support\Carbon::parse($lead->campaign_touched_at)->format('d/m/Y H:i') : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-slate-500">Nenhum lead associado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($leads->hasPages())<div class="mt-4">{{ $leads->withQueryString()->links() }}</div>@endif
        </x-form-section>
    </div><aside class="space-y-4">
        <x-form-section title="Responsável / Canal"><dl class="detail-grid xl:grid-cols-1"><div class="detail-item"><dt class="detail-label">Responsável</dt><dd class="detail-value">{{ $campaign->owner?->name ?? 'Não atribuído' }}{{ $campaign->owner && ! $campaign->owner->active ? ' — Inativo' : '' }}</dd></div><div class="detail-item"><dt class="detail-label">Canal</dt><dd class="detail-value">{{ $campaign->channel?->name ?? 'Não definido' }}{{ $campaign->channel && ! $campaign->channel->active ? ' — Inativo' : '' }}</dd></div></dl></x-form-section>
        <x-form-section title="Auditoria"><dl class="detail-grid xl:grid-cols-1"><div class="detail-item"><dt class="detail-label">Criada em</dt><dd class="detail-value">{{ $campaign->created_at?->format('d/m/Y H:i') }}</dd></div><div class="detail-item"><dt class="detail-label">Atualizada em</dt><dd class="detail-value">{{ $campaign->updated_at?->format('d/m/Y H:i') }}</dd></div></dl></x-form-section>
        @if(auth()->user()->can('update', $campaign) && auth()->user()->can('viewAny', App\Models\Lead::class))
            <x-form-section title="Associar lead" description="Busque por nome, empresa ou e-mail. Até 20 resultados são carregados por busca.">
                <form method="GET" action="{{ route('campaigns.show', $campaign) }}">
                    <label class="label" for="lead_q">Buscar lead</label>
                    <div class="flex gap-2"><input class="input" id="lead_q" name="lead_q" value="{{ $leadQuery }}" minlength="2" required><button class="btn-secondary" type="submit">Buscar</button></div>
                </form>
                @if(mb_strlen($leadQuery) >= 2)
                    <form class="mt-4" method="POST" action="{{ route('campaigns.leads.store', $campaign) }}">
                        @csrf
                        <label class="label" for="lead_id">Lead</label>
                        <select class="input" id="lead_id" name="lead_id" required>
                            <option value="">Selecione</option>
                            @foreach($leadOptions as $option)<option value="{{ $option->id }}" @selected((string) old('lead_id') === (string) $option->id)>{{ $option->name ?: 'Lead #'.$option->id }}{{ $option->company_name ? ' — '.$option->company_name : '' }}</option>@endforeach
                        </select>
                        @error('lead_id')<p class="field-error">{{ $message }}</p>@enderror
                        @if($leadOptions->isEmpty())<p class="mt-2 text-sm text-slate-500">Nenhum lead encontrado.</p>@else<button class="btn-primary mt-3" type="submit">Associar lead</button>@endif
                    </form>
                @endif
            </x-form-section>
        @endif
        @can('delete', $campaign)<section class="rounded-xl border border-red-200 bg-white p-4 shadow-sm"><h2 class="font-semibold text-red-900">Excluir campanha</h2><p class="mt-1 text-sm text-slate-600">A campanha será arquivada por soft delete.</p><form class="mt-3" method="POST" action="{{ route('campaigns.destroy', $campaign) }}" onsubmit="return confirm('Deseja excluir esta campanha?')">@csrf @method('DELETE')<button class="btn-danger" type="submit">Excluir</button></form></section>@endcan
    </aside></div>
</x-layouts.app>
