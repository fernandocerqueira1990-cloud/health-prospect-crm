<x-layouts.app title="{{ $activity->subject }}">
    @php
        $typeLabel = match($activity->type) {
            'call' => 'Ligação',
            'email' => 'E-mail',
            'whatsapp' => 'WhatsApp',
            'meeting' => 'Reunião',
            'note' => 'Nota',
            default => 'Outro',
        };
    @endphp

    <x-page-header
        title="{{ $activity->subject }}"
        description="{{ $typeLabel }}"
    >
        <x-slot:actions>
            @can('update', $activity)
                <a
                    class="btn-primary"
                    href="{{ route('activities.edit', $activity) }}"
                >
                    Editar
                </a>
            @endcan

            <a class="btn-secondary" href="{{ route('activities.index') }}">
                Voltar
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="card">
                <h2 class="text-lg font-semibold text-slate-950">
                    Interação
                </h2>

                <dl class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Tipo
                        </dt>
                        <dd class="mt-1 text-slate-900">
                            {{ $typeLabel }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Direção
                        </dt>
                        <dd class="mt-1 text-slate-900">
                            {{
                                match($activity->direction) {
                                    'outbound' => 'Saída',
                                    'inbound' => 'Entrada',
                                    default => '—',
                                }
                            }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Data e hora
                        </dt>
                        <dd class="mt-1 text-slate-900">
                            {{ $activity->occurred_at?->format('d/m/Y H:i') ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Duração
                        </dt>
                        <dd class="mt-1 text-slate-900">
                            {{
                                $activity->duration_minutes
                                    ? $activity->duration_minutes.' minutos'
                                    : '—'
                            }}
                        </dd>
                    </div>
                </dl>

                @if($activity->description)
                    <div class="mt-6 border-t border-slate-200 pt-5">
                        <p class="text-xs font-semibold uppercase text-slate-500">
                            Descrição
                        </p>

                        <p class="mt-2 whitespace-pre-line text-slate-800">
                            {{ $activity->description }}
                        </p>
                    </div>
                @endif

                @if($activity->outcome)
                    <div class="mt-6 border-t border-slate-200 pt-5">
                        <p class="text-xs font-semibold uppercase text-slate-500">
                            Resultado
                        </p>

                        <p class="mt-2 whitespace-pre-line text-slate-800">
                            {{ $activity->outcome }}
                        </p>
                    </div>
                @endif
            </section>

            <section class="card">
                <h2 class="text-lg font-semibold text-slate-950">
                    Vínculos comerciais
                </h2>

                <dl class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Empresa
                        </dt>
                        <dd class="mt-1">
                            @if($activity->company)
                                <a
                                    class="font-semibold text-teal-700"
                                    href="{{ route('companies.show', $activity->company) }}"
                                >
                                    {{
                                        $activity->company->trade_name
                                        ?: $activity->company->legal_name
                                    }}
                                </a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Contato
                        </dt>
                        <dd class="mt-1">
                            @if($activity->contact)
                                <a
                                    class="font-semibold text-teal-700"
                                    href="{{ route('contacts.show', $activity->contact) }}"
                                >
                                    {{ $activity->contact->name }}
                                </a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Lead
                        </dt>
                        <dd class="mt-1">
                            @if($activity->lead)
                                <a
                                    class="font-semibold text-teal-700"
                                    href="{{ route('leads.show', $activity->lead) }}"
                                >
                                    {{
                                        $activity->lead->name
                                        ?: $activity->lead->company_name
                                        ?: 'Lead #'.$activity->lead->id
                                    }}
                                </a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Oportunidade
                        </dt>
                        <dd class="mt-1">
                            @if($activity->opportunity)
                                <a
                                    class="font-semibold text-teal-700"
                                    href="{{ route('opportunities.show', $activity->opportunity) }}"
                                >
                                    {{ $activity->opportunity->title }}
                                </a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                </dl>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="card">
                <h2 class="text-lg font-semibold text-slate-950">
                    Responsabilidade
                </h2>

                <dl class="mt-5 space-y-5">
                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Responsável
                        </dt>
                        <dd class="mt-1 text-slate-900">
                            {{ $activity->assignedUser?->name ?? 'Não atribuído' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Registrado por
                        </dt>
                        <dd class="mt-1 text-slate-900">
                            {{ $activity->createdByUser?->name ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase text-slate-500">
                            Criado em
                        </dt>
                        <dd class="mt-1 text-slate-900">
                            {{ $activity->created_at?->format('d/m/Y H:i') }}
                        </dd>
                    </div>
                </dl>
            </section>

            @can('delete', $activity)
                <section class="card border-red-200">
                    <h2 class="font-semibold text-red-700">
                        Excluir atividade
                    </h2>

                    <p class="mt-2 text-sm text-slate-500">
                        A atividade será arquivada por soft delete.
                    </p>

                    <form
                        class="mt-4"
                        method="POST"
                        action="{{ route('activities.destroy', $activity) }}"
                        onsubmit="return confirm('Deseja excluir esta atividade?')"
                    >
                        @csrf
                        @method('DELETE')

                        <button
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700"
                            type="submit"
                        >
                            Excluir
                        </button>
                    </form>
                </section>
            @endcan
        </aside>
    </div>
</x-layouts.app>
