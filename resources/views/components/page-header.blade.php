@props(['title', 'description' => null])
<div {{ $attributes->class(['page-header']) }}>
    <div class="page-header-copy">
        <h1 class="page-title">{{ $title }}</h1>
        @if($description)
            <p class="page-subtitle">{{ $description }}</p>
        @endif
    </div>
    @if(isset($actions))
        <div class="page-header-actions">{{ $actions }}</div>
    @endif
</div>
