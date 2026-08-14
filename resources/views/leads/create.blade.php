<x-layouts.app title="Novo lead">
    <x-page-header
        title="Novo lead"
        description="Cadastre um potencial cliente e registre sua origem comercial."
    />

    <form class="card" method="POST" action="{{ route('leads.store') }}">
        @csrf

        @include('leads._form', [
            'lead' => null,
        ])
    </form>
</x-layouts.app>
