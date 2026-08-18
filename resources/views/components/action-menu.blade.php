@props(['label' => 'Mais ações'])
<details {{ $attributes->class(['action-menu']) }}>
    <summary class="btn-ghost btn-icon cursor-pointer" aria-label="{{ $label }}" title="{{ $label }}">
        <span aria-hidden="true">•••</span>
    </summary>
    <div class="action-menu-panel">{{ $slot }}</div>
</details>
