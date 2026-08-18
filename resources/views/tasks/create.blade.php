<x-layouts.app title="Nova tarefa">
    <x-page-header
        title="Nova tarefa"
        description="Cadastre uma nova pendência ou compromisso."
    />

    <form
        class="card"
        method="POST"
        action="{{ route('tasks.store') }}"
    >
        @csrf

        @include('tasks._form', [
            'task' => null,
        ])
    </form>
</x-layouts.app>
