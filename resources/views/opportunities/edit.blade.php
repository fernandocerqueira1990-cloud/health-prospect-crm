<x-layouts.app title="Editar oportunidade">
    <x-page-header
        title="Editar oportunidade"
        description="Atualize os dados comerciais sem alterar o histórico do pipeline."
    />

    <form
        class="card"
        method="POST"
        action="{{ route('opportunities.update', $opportunity) }}"
    >
        @csrf
        @method('PUT')

        @include('opportunities._form', [
            'opportunity' => $opportunity,
        ])
    </form>
</x-layouts.app>
