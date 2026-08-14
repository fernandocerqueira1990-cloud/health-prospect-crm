<x-layouts.app title="Editar empresa">
    <x-page-header title="Editar empresa" description="Atualize os dados de {{ $company->legal_name }}." />
    <form class="card" method="POST" action="{{ route('companies.update', $company) }}">
        @csrf
        @method('PUT')
        @include('companies._form')
    </form>
</x-layouts.app>
