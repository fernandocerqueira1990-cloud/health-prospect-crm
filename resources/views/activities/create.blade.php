<x-layouts.app title="Nova atividade">
    <x-page-header
        title="Nova atividade"
        description="Registre uma interação comercial com empresa, contato, lead ou oportunidade."
    />

    <form
        class="card"
        method="POST"
        action="{{ route('activities.store') }}"
    >
        @csrf

        @include('activities._form', [
            'activity' => null,
        ])
    </form>
</x-layouts.app>
