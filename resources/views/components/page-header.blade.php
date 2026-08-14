@props(['title', 'description' => null])
<div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-950">{{ $title }}</h1>
        @if($description)<p class="mt-1 text-sm text-slate-600">{{ $description }}</p>@endif
    </div>
    <div>{{ $actions ?? '' }}</div>
</div>
