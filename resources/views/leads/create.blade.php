<x-layouts.app title="Novo lead">
    <x-page-header
        title="Novo lead"
        description="Cadastre uma nova oportunidade de prospecção comercial."
    />

    <form method="POST" action="{{ route('leads.store') }}">
        @csrf

        @include('leads._form', [
            'lead' => null,
        ])
    </form>
</x-layouts.app>
