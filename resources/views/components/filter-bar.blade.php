<div {{ $attributes->class(['filter-bar']) }}>
    <div class="filter-bar-fields">
        {{ $slot }}
        @if(isset($actions))
            <div class="filter-bar-actions">{{ $actions }}</div>
        @endif
    </div>
</div>
