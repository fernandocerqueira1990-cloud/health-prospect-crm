<x-layouts.app title="Nova empresa">
    <x-page-header title="Nova empresa" description="Cadastre uma nova organização comercial." />
    <form method="POST" action="{{ route('companies.store') }}">
        @csrf
        @include('companies._form', ['company' => null])
    </form>
</x-layouts.app>
