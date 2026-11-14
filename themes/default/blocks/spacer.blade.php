<div id="{{ $blockId }}" aria-hidden="true" @class([
    'container-shop',
    'py-3' => ($block['height'] ?? 'medium') === 'small',
    'py-8' => ($block['height'] ?? 'medium') === 'medium',
    'py-16' => ($block['height'] ?? 'medium') === 'large',
])>
    @if (! empty($block['divider']))
        <hr class="border-current opacity-15">
    @endif
</div>
