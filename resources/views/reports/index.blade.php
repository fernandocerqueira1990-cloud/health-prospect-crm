<x-layouts.app title="Relatórios">
    <x-page-header title="Relatórios" description="Indicadores comerciais e análise de desempenho." />

    <section class="card mb-5" aria-labelledby="report-period-title">
        <div class="mb-4">
            <h2 id="report-period-title" class="text-base font-bold text-slate-950">Período analisado</h2>
            <p class="mt-0.5 text-sm text-slate-500">Filtre os registros pela data em que foram criados.</p>
        </div>

        <form method="GET" action="{{ route('reports.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="sm:w-48">
                <label class="label" for="date_from">Data inicial</label>
                <input class="input" id="date_from" name="date_from" type="date" value="{{ old('date_from', $filters['date_from'] ?? '') }}">
                @error('date_from')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="sm:w-48">
                <label class="label" for="date_to">Data final</label>
                <input class="input" id="date_to" name="date_to" type="date" value="{{ old('date_to', $filters['date_to'] ?? '') }}">
                @error('date_to')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex gap-2">
                <button class="btn-primary" type="submit">Aplicar</button>
                <a class="btn-secondary" href="{{ route('reports.index') }}">Limpar</a>
            </div>
        </form>
    </section>

    <section class="mt-5" aria-labelledby="acquisition-title">
        <div class="mb-4">
            <h2 id="acquisition-title" class="text-lg font-bold text-slate-950">Origem e aquisição</h2>
            <p class="mt-0.5 text-sm text-slate-500">Como os Leads criados no período chegaram ao CRM.</p>
            <p class="mt-1 text-xs text-slate-500">Origem e canal consideram o First Touch de cada Lead, com fallback para o cadastro do Lead.</p>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            @foreach([
                ['title' => 'Origem dos Leads', 'items' => $acquisition['sources'], 'empty' => 'Nenhuma origem registrada no período.'],
                ['title' => 'Canais', 'items' => $acquisition['channels'], 'empty' => 'Nenhum canal registrado no período.'],
            ] as $distribution)
                <article class="card">
                    <h3 class="text-base font-bold text-slate-950">{{ $distribution['title'] }}</h3>
                    <div class="mt-4 space-y-4">
                        @forelse($distribution['items'] as $item)
                            <div>
                                <div class="mb-1.5 flex items-baseline justify-between gap-4 text-sm">
                                    <p class="font-semibold text-slate-800">{{ $item['name'] }}</p>
                                    <p class="shrink-0 text-slate-600">{{ $item['count'] }} · <span class="font-semibold text-slate-800">{{ number_format($item['percentage'], 1, ',', '.') }}%</span></p>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-label="{{ $item['name'] }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $item['percentage'] }}">
                                    <div class="h-full rounded-full bg-blue-600" style="width: {{ $item['percentage'] }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm font-medium text-slate-600">{{ $distribution['empty'] }}</p>
                        @endforelse
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="mt-5" aria-labelledby="campaign-performance-title">
        <div class="mb-4">
            <h2 id="campaign-performance-title" class="text-lg font-bold text-slate-950">Performance por campanha</h2>
            <p class="mt-0.5 text-sm text-slate-500">Resultados das campanhas atribuídas aos Leads criados no período.</p>
            <p class="mt-1 text-xs text-slate-500">Um Lead pode aparecer em campanhas diferentes; os totais das campanhas não representam atribuição exclusiva.</p>
        </div>

        <div class="card overflow-hidden p-0">
            @if($acquisition['campaigns'] === [])
                <p class="p-5 text-sm font-medium text-slate-600">Nenhuma campanha com Leads atribuídos no período.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Campanha</th>
                                <th class="px-4 py-3 text-right">Leads</th>
                                <th class="px-4 py-3 text-right">Oportunidades</th>
                                <th class="px-4 py-3 text-right">Ganhas</th>
                                <th class="px-4 py-3 text-right">Lead → Oportunidade</th>
                                <th class="px-4 py-3 text-right">Oportunidade → Ganho</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            @foreach($acquisition['campaigns'] as $campaign)
                                <tr>
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-slate-900">{{ $campaign['name'] }}</p>
                                        @if($campaign['channel'])<p class="text-xs text-slate-500">{{ $campaign['channel'] }}</p>@endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-slate-700">{{ $campaign['attributed_leads'] }}</td>
                                    <td class="px-4 py-3 text-right font-medium text-slate-700">{{ $campaign['opportunities'] }}</td>
                                    <td class="px-4 py-3 text-right font-medium text-slate-700">{{ $campaign['won_opportunities'] }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ number_format($campaign['lead_to_opportunity_conversion'], 1, ',', '.') }}%</td>
                                    <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ number_format($campaign['opportunity_to_won_conversion'], 1, ',', '.') }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <x-metric-card label="Leads criados" :value="$metrics['leads']" help="Leads cadastrados no período" />
        <x-metric-card label="Oportunidades" :value="$metrics['opportunities']" help="Oportunidades criadas no período" />
        <x-metric-card label="Oportunidades abertas" :value="$metrics['open_opportunities']" help="Sem ganho ou perda registrados" />
        <x-metric-card label="Oportunidades ganhas" :value="$metrics['won_opportunities']" help="Com ganho registrado" />
        <x-metric-card label="Oportunidades perdidas" :value="$metrics['lost_opportunities']" help="Com perda registrada" />
        <x-metric-card label="Lead → Oportunidade" :value="number_format($metrics['lead_to_opportunity_conversion'], 1, ',', '.').'%'" help="Leads distintos que geraram oportunidade" />
        <x-metric-card label="Oportunidade → Ganho" :value="number_format($metrics['opportunity_to_won_conversion'], 1, ',', '.').'%'" help="Oportunidades com ganho registrado" />
    </div>

    <section class="mt-5" aria-labelledby="executive-view-title">
        <div class="mb-4">
            <h2 id="executive-view-title" class="text-lg font-bold text-slate-950">Visão executiva</h2>
            <p class="mt-0.5 text-sm text-slate-500">Valores comerciais das oportunidades criadas no período.</p>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach([
                'open_pipeline' => 'Pipeline aberto',
                'won_value' => 'Valor ganho',
                'lost_value' => 'Valor perdido',
                'average_won_ticket' => 'Ticket médio ganho',
            ] as $metric => $label)
                <section class="metric-card">
                    <h3 class="text-sm font-semibold text-slate-500">{{ $label }}</h3>
                    @forelse($executiveMetrics[$metric] as $value)
                        <p class="mt-2 text-xl font-bold tracking-tight text-slate-950">
                            {{ $value['currency'] }} {{ number_format((float) $value['amount'], 2, ',', '.') }}
                        </p>
                    @empty
                        <p class="mt-2 text-sm font-medium text-slate-500">Sem valores no período</p>
                    @endforelse
                </section>
            @endforeach
        </div>
    </section>

    <section class="mt-5" aria-labelledby="commercial-funnel-title">
        <div class="mb-4">
            <h2 id="commercial-funnel-title" class="text-lg font-bold text-slate-950">Funil comercial</h2>
            <p class="mt-0.5 text-sm text-slate-500">Distribuição atual das oportunidades criadas no período pelas etapas do pipeline.</p>
            <p class="mt-1 text-xs text-slate-500">Esta é uma fotografia das etapas atuais, não uma reconstrução histórica do período.</p>
        </div>

        @forelse($funnels as $funnel)
            <article class="card mb-4">
                <div class="flex flex-col gap-1 border-b border-slate-200 pb-4 sm:flex-row sm:items-center sm:justify-between">
                    <h3 class="text-base font-bold text-slate-950">{{ $funnel['name'] }}</h3>
                    <p class="text-sm font-medium text-slate-500">{{ $funnel['total'] }} {{ $funnel['total'] === 1 ? 'oportunidade' : 'oportunidades' }}</p>
                </div>

                <div class="mt-4 space-y-4">
                    @foreach($funnel['stages'] as $stage)
                        <div>
                            <div class="mb-1.5 flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 text-sm">
                                <p class="font-semibold text-slate-800">
                                    {{ $stage['name'] }}
                                    @if($stage['active'] === false)
                                        <span class="font-normal text-slate-500">(inativa)</span>
                                    @endif
                                </p>
                                <p class="text-slate-600">
                                    {{ $stage['count'] }} {{ $stage['count'] === 1 ? 'oportunidade' : 'oportunidades' }} ·
                                    <span class="font-semibold text-slate-800">{{ number_format($stage['percentage'], 1, ',', '.') }}%</span>
                                </p>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-label="{{ $stage['name'] }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $stage['percentage'] }}">
                                <div class="h-full rounded-full bg-blue-600" style="width: {{ $stage['percentage'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 grid gap-3 border-t border-slate-200 pt-4 sm:grid-cols-3">
                    @foreach([
                        ['label' => 'Taxa em aberto', 'count' => $funnel['open'], 'rate' => $funnel['open_rate']],
                        ['label' => 'Taxa de ganho', 'count' => $funnel['won'], 'rate' => $funnel['win_rate']],
                        ['label' => 'Taxa de perda', 'count' => $funnel['lost'], 'rate' => $funnel['loss_rate']],
                    ] as $conversion)
                        <div class="rounded-lg bg-slate-50 px-4 py-3">
                            <p class="text-sm font-semibold text-slate-500">{{ $conversion['label'] }}</p>
                            <p class="mt-1 text-xl font-bold text-slate-950">{{ number_format($conversion['rate'], 1, ',', '.') }}%</p>
                            <p class="text-xs text-slate-500">{{ $conversion['count'] }} de {{ $funnel['total'] }}</p>
                        </div>
                    @endforeach
                </div>
            </article>
        @empty
            <div class="card">
                <p class="text-sm font-medium text-slate-600">Nenhuma oportunidade encontrada no período selecionado.</p>
            </div>
        @endforelse
    </section>

    <section class="card mt-5" aria-labelledby="next-reports-title">
        <h2 id="next-reports-title" class="text-base font-bold text-slate-950">Próximas análises da Sprint</h2>
        <p class="mt-1 text-sm text-slate-500">Esta área será ampliada com análises de pipeline e tempo por etapa.</p>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach(['Pipeline e etapas'] as $block)
                <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">{{ $block }}</div>
            @endforeach
        </div>
    </section>
</x-layouts.app>
