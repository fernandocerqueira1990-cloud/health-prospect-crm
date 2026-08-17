@props(['label', 'value' => null, 'help' => null])
<section {{ $attributes->class(['metric-card']) }}>
    <p class="metric-label">{{ $label }}</p>
    <div class="metric-value">{{ $value ?? $slot }}</div>
    @if($help)
        <p class="metric-help">{{ $help }}</p>
    @endif
</section>
