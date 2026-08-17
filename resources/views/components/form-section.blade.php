@props(['title' => null, 'description' => null])
<section {{ $attributes->class(['form-section']) }}>
    @if($title || $description || isset($actions))
        <header class="form-section-header">
            <div>
                @if($title)<h2 class="section-title">{{ $title }}</h2>@endif
                @if($description)<p class="section-description">{{ $description }}</p>@endif
            </div>
            @if(isset($actions))<div class="page-toolbar-actions">{{ $actions }}</div>@endif
        </header>
    @endif
    <div class="form-section-body">{{ $slot }}</div>
    @if(isset($footer))
        <footer class="form-section-footer">{{ $footer }}</footer>
    @endif
</section>
