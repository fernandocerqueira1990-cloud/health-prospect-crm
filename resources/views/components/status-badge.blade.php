@props(['variant' => 'neutral'])
@php
    $class = match($variant) {
        'info', 'success', 'warning', 'danger' => 'badge-'.$variant,
        default => 'badge-neutral',
    };
@endphp
<span {{ $attributes->class([$class]) }}>{{ $slot }}</span>
