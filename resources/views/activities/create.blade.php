<x-layouts.app title="Nova atividade">
    <x-page-header
        title="Nova atividade"
        description="Registre uma nova interação ou ação comercial."
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
