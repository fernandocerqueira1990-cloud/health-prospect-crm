<x-layouts.app title="Notificações">
    <x-page-header title="Notificações" description="Alertas comerciais internos sobre prazos, follow-ups e oportunidades que exigem atenção.">
        <x-slot:actions>
            @if($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    @method('PATCH')
                    <button class="btn-secondary" type="submit">Marcar todas como lidas</button>
                </form>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 flex items-center justify-between gap-3">
        <p class="text-sm text-slate-500">{{ $unreadCount }} não lida{{ $unreadCount === 1 ? '' : 's' }}</p>
    </div>

    <section class="card p-0">
        <div class="divide-y divide-slate-100">
            @forelse($notifications as $notification)
                @php
                    $data = $notification->data;
                    $severity = $data['severity'] ?? 'info';
                    $accent = match($severity) {
                        'danger' => 'border-l-rose-500 bg-rose-50/40',
                        'warning' => 'border-l-amber-500 bg-amber-50/40',
                        default => 'border-l-crm-sky bg-crm-ice/30',
                    };
                @endphp

                <div class="border-l-4 px-4 py-4 sm:px-5 {{ $accent }} {{ $notification->read_at ? 'opacity-70' : '' }}">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-bold text-slate-950">{{ $data['title'] ?? 'Alerta comercial' }}</p>
                                @if(!$notification->read_at)
                                    <span class="rounded-full bg-crm-blue px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white">Nova</span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-slate-600">{{ $data['message'] ?? 'Verifique esta pendência comercial.' }}</p>
                            <p class="mt-1 text-xs text-slate-400">{{ $notification->created_at->format('d/m/Y H:i') }}</p>
                        </div>

                        <div class="shrink-0">
                            @if(!$notification->read_at)
                                <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn-primary" type="submit">Abrir</button>
                                </form>
                            @elseif(!empty($data['url']))
                                <a class="btn-secondary" href="{{ $data['url'] }}">Ver item</a>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <x-empty-state title="Nenhuma notificação comercial." description="Quando houver pendências relevantes, elas aparecerão aqui." class="py-10" />
            @endforelse
        </div>
    </section>

    @if($notifications->hasPages())
        <div class="mt-5">{{ $notifications->links() }}</div>
    @endif
</x-layouts.app>
