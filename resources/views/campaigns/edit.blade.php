<x-layouts.app title="Editar campanha">
    <x-page-header title="Editar campanha" description="Atualize o planejamento e a atribuição da campanha." />
    <form class="card" method="POST" action="{{ route('campaigns.update', $campaign) }}">
        @csrf
        @method('PUT')
        @include('campaigns._form', ['campaign' => $campaign])
    </form>
</x-layouts.app>
