@props(['variant' => 'neutral'])

@php
    $class = match($variant) {
        'info' => 'status-badge status-badge-info',
        'success' => 'status-badge status-badge-success',
        'warning' => 'status-badge status-badge-warning',
        'danger' => 'status-badge status-badge-danger',
        default => 'status-badge status-badge-neutral',
    };
@endphp

<span {{ $attributes->class([$class]) }}>
    {{ $slot }}
</span>
