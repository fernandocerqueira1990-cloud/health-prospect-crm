<x-layouts.app title="Nova oportunidade">
    <x-page-header
        title="Nova oportunidade"
        description="Cadastre um novo negócio no pipeline comercial."
    />

    <form
        class="card"
        method="POST"
        action="{{ route('opportunities.store') }}"
    >
        @csrf

        @include('opportunities._form', [
            'opportunity' => null,
        ])
    </form>
</x-layouts.app>
