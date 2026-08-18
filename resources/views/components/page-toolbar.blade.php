<div {{ $attributes->class(['page-toolbar']) }}>
    <div class="page-toolbar-content">{{ $slot }}</div>
    @if(isset($actions))
        <div class="page-toolbar-actions">{{ $actions }}</div>
    @endif
</div>
