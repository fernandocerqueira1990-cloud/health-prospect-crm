<x-layouts.app title="Nova tarefa">
    <x-page-header
        title="Nova tarefa"
        description="Crie uma pendência, compromisso ou próximo passo comercial."
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
