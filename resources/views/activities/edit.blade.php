<x-layouts.app title="Editar atividade">
    <x-page-header
        title="Editar atividade"
        description="Atualize os dados da interação comercial."
    />

    <form
        class="card"
        method="POST"
        action="{{ route('activities.update', $activity) }}"
    >
        @csrf
        @method('PUT')

        @include('activities._form', [
            'activity' => $activity,
        ])
    </form>
</x-layouts.app>
