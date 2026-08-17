@php($roles=['decision_maker'=>'Decisor','influencer'=>'Influenciador','champion'=>'Patrocinador interno','user'=>'Usuário','technical'=>'Técnico','procurement'=>'Compras','financial'=>'Financeiro','gatekeeper'=>'Guardião','blocker'=>'Bloqueador','other'=>'Outro'])
@php($levels=['low'=>'Baixa','medium'=>'Média','high'=>'Alta','critical'=>'Crítica'])
<x-layouts.app :title="$contact->name">
    <x-page-header :title="$contact->name" description="Detalhes da pessoa.">
        <x-slot:actions><div class="flex flex-wrap gap-2">@can('update',$contact)<a class="btn-primary" href="{{ route('contacts.edit',$contact) }}">Editar</a>@endcan<a class="btn-secondary" href="{{ route('contacts.index') }}">Voltar</a></div></x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 xl:grid-cols-3">
        <div class="space-y-4 xl:col-span-2">
            <x-form-section title="Identificação profissional">
                <dl class="detail-grid lg:grid-cols-3">
                    <div class="detail-item lg:col-span-2"><dt class="detail-label">Empresa</dt><dd class="detail-value">@if($contact->company->trashed())<span>{{ $contact->company->legal_name }}</span> <x-status-badge variant="warning">Arquivada</x-status-badge>@else<a class="text-teal-700 hover:underline" href="{{ route('companies.show',$contact->company) }}">{{ $contact->company->legal_name }}</a>@endif</dd></div>
                    <div class="detail-item"><dt class="detail-label">Status</dt><dd class="detail-value"><x-status-badge :variant="$contact->active ? 'success' : 'neutral'">{{ $contact->active ? 'Ativo' : 'Inativo' }}</x-status-badge></dd></div>
                    <div class="detail-item"><dt class="detail-label">Cargo</dt><dd class="detail-value">{{ $contact->job_title ?? '—' }}</dd></div>
                    <div class="detail-item"><dt class="detail-label">Departamento</dt><dd class="detail-value">{{ $contact->department ?? '—' }}</dd></div>
                    <div class="detail-item"><dt class="detail-label">Contato principal</dt><dd class="detail-value"><x-status-badge :variant="$contact->is_primary ? 'info' : 'neutral'">{{ $contact->is_primary ? 'Sim' : 'Não' }}</x-status-badge></dd></div>
                </dl>
            </x-form-section>

            <x-form-section title="Contato">
                <dl class="detail-grid lg:grid-cols-4">
                    <div class="detail-item"><dt class="detail-label">E-mail</dt><dd class="detail-value">@if($contact->email)<a class="text-teal-700 hover:underline" href="mailto:{{ $contact->email }}">{{ $contact->email }}</a>@else — @endif</dd></div>
                    <div class="detail-item"><dt class="detail-label">Telefone</dt><dd class="detail-value">@if($contact->phone)<a class="text-teal-700 hover:underline" href="tel:{{ $contact->phone }}">{{ $contact->phone }}</a>@else — @endif</dd></div>
                    <div class="detail-item"><dt class="detail-label">WhatsApp</dt><dd class="detail-value">@if($contact->whatsapp)<a class="text-teal-700 hover:underline" href="https://wa.me/{{ preg_replace('/\D/','',$contact->whatsapp) }}" target="_blank" rel="noopener noreferrer">{{ $contact->whatsapp }}</a>@else — @endif</dd></div>
                    <div class="detail-item"><dt class="detail-label">LinkedIn</dt><dd class="detail-value">@if($contact->linkedin_url)<a class="text-teal-700 hover:underline" href="{{ $contact->linkedin_url }}" target="_blank" rel="noopener noreferrer">Abrir perfil</a>@else — @endif</dd></div>
                </dl>
            </x-form-section>

            <x-form-section title="Observações">
                <p class="whitespace-pre-line text-sm leading-6 text-slate-700">{{ $contact->notes ?? 'Nenhuma observação registrada.' }}</p>
            </x-form-section>
        </div>

        <aside class="space-y-4">
            <x-form-section title="Papel comercial">
                <dl class="detail-grid xl:grid-cols-1">
                    <div class="detail-item"><dt class="detail-label">Papel na decisão</dt><dd class="detail-value"><x-status-badge variant="info">{{ $roles[$contact->decision_role] ?? 'Não definido' }}</x-status-badge></dd></div>
                    <div class="detail-item"><dt class="detail-label">Influência</dt><dd class="detail-value"><x-status-badge :variant="$contact->influence_level === 'critical' ? 'danger' : ($contact->influence_level === 'high' ? 'warning' : 'neutral')">{{ $levels[$contact->influence_level] ?? 'Não definida' }}</x-status-badge></dd></div>
                </dl>
            </x-form-section>
            <x-form-section title="Metadados">
                <dl class="detail-grid xl:grid-cols-1">
                    <div class="detail-item"><dt class="detail-label">Criado em</dt><dd class="detail-value">{{ $contact->created_at->format('d/m/Y H:i') }}</dd></div>
                    <div class="detail-item"><dt class="detail-label">Atualizado em</dt><dd class="detail-value">{{ $contact->updated_at->format('d/m/Y H:i') }}</dd></div>
                </dl>
            </x-form-section>
            @can('delete',$contact)<section class="rounded-xl border border-red-200 bg-white p-4 shadow-sm"><h2 class="font-semibold text-red-900">Excluir contato</h2><p class="mt-1 text-sm text-slate-600">O contato será arquivado por soft delete.</p><form class="mt-3" method="POST" action="{{ route('contacts.destroy',$contact) }}" onsubmit="return confirm('Confirma a exclusão deste contato?')">@csrf @method('DELETE')<button class="btn-danger" type="submit">Excluir contato</button></form></section>@endcan
        </aside>
    </div>
</x-layouts.app>
