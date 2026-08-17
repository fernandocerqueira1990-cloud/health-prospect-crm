<x-layouts.app title="Editar lead">
    <x-page-header
        title="Editar lead"
        description="Atualize os dados comerciais de {{ $lead->name ?: 'Lead #'.$lead->id }}."
    />

    <form class="card" method="POST" action="{{ route('leads.update', $lead) }}">
        @csrf
        @method('PUT')

        @include('leads._form')
    </form>
</x-layouts.app>
