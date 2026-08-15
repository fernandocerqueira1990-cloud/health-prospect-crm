<x-layouts.app title="Editar tarefa">
    <x-page-header
        title="Editar tarefa"
        description="Atualize status, prioridade, prazo e vínculos."
    />

    <form
        class="card"
        method="POST"
        action="{{ route('tasks.update', $task) }}"
    >
        @csrf
        @method('PUT')

        @include('tasks._form', [
            'task' => $task,
        ])
    </form>
</x-layouts.app>
