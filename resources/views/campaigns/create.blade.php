<x-layouts.app title="Nova campanha">
    <x-page-header title="Nova campanha" description="Planeje uma nova iniciativa de aquisição." />
    <form class="card" method="POST" action="{{ route('campaigns.store') }}">
        @csrf
        @include('campaigns._form', ['campaign' => null])
    </form>
</x-layouts.app>
