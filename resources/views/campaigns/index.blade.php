@php
    $statusLabels = ['draft' => 'Rascunho', 'planned' => 'Planejada', 'active' => 'Ativa', 'paused' => 'Pausada', 'completed' => 'Concluída', 'cancelled' => 'Cancelada'];
    $statusVariants = ['draft' => 'neutral', 'planned' => 'info', 'active' => 'success', 'paused' => 'warning', 'completed' => 'success', 'cancelled' => 'danger'];
@endphp
<x-layouts.app title="Campanhas">
    <x-page-header title="Campanhas" description="Planejamento de campanhas, canais e parâmetros de aquisição.">
        <x-slot:actions>@can('create', App\Models\Campaign::class)<a class="btn-primary" href="{{ route('campaigns.create') }}">Nova campanha</a>@endcan</x-slot:actions>
    </x-page-header>
    <div class="listing-summary">@if($campaigns->total()) Mostrando {{ $campaigns->firstItem() }}–{{ $campaigns->lastItem() }} de {{ $campaigns->total() }} campanhas @else Nenhuma campanha encontrada @endif</div>
    <div class="table-wrap">
        <table class="table listing-table"><thead><tr><th>Nome</th><th>Status</th><th>Canal</th><th>Responsável</th><th>Período</th><th>Orçamento</th><th class="text-right">AÇÕES</th></tr></thead>
            <tbody>@forelse($campaigns as $campaign)<tr>
                <td><a class="table-primary-link" href="{{ route('campaigns.show', $campaign) }}">{{ $campaign->name }}</a>@if($campaign->description)<span class="table-secondary-line max-w-72 truncate" title="{{ $campaign->description }}">{{ $campaign->description }}</span>@endif</td>
                <td><x-status-badge :variant="$statusVariants[$campaign->status] ?? 'neutral'">{{ $statusLabels[$campaign->status] ?? $campaign->status }}</x-status-badge></td>
                <td>{{ $campaign->channel?->name ?? '—' }}</td><td>{{ $campaign->owner?->name ?? 'Não atribuído' }}</td>
                <td class="whitespace-nowrap">@if($campaign->start_date || $campaign->end_date){{ $campaign->start_date?->format('d/m/Y') ?? '…' }} – {{ $campaign->end_date?->format('d/m/Y') ?? '…' }}@else — @endif</td>
                <td class="whitespace-nowrap">{{ $campaign->budget !== null ? $campaign->currency.' '.number_format((float) $campaign->budget, 2, ',', '.') : '—' }}</td>
                <td><div class="table-actions">@can('view', $campaign)<a class="table-action-link" href="{{ route('campaigns.show', $campaign) }}">Ver</a>@endcan @can('update', $campaign)<a class="table-action-link" href="{{ route('campaigns.edit', $campaign) }}">Editar</a>@endcan</div></td>
            </tr>@empty<tr><td colspan="7"><x-empty-state title="Nenhuma campanha cadastrada ainda." description="As campanhas cadastradas aparecerão nesta lista." /></td></tr>@endforelse</tbody>
        </table>
    </div>
    @if($campaigns->hasPages())<div class="listing-pagination">{{ $campaigns->links() }}</div>@endif
</x-layouts.app>
