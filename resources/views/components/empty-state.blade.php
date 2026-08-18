@props(['title' => 'Nenhum registro encontrado', 'description' => null])
<div {{ $attributes->class(['empty-state']) }}>
    @if(isset($icon))
        <div class="empty-state-icon" aria-hidden="true">{{ $icon }}</div>
    @endif
    <p class="empty-state-title">{{ $title }}</p>
    @if($description)
        <p class="empty-state-description">{{ $description }}</p>
    @endif
    @if(isset($action))
        <div class="empty-state-action">{{ $action }}</div>
    @endif
</div>
