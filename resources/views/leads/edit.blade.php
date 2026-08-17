<x-layouts.app title="Editar lead">
    <x-page-header
        title="Editar lead"
        description="Atualize os dados e a qualificação comercial de {{ $lead->name ?: 'Lead #'.$lead->id }}."
    />

    <form method="POST" action="{{ route('leads.update', $lead) }}">
        @csrf
        @method('PUT')

        @include('leads._form')
    </form>
</x-layouts.app>
