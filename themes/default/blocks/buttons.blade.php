@if (! empty($block['items']))
    <div id="{{ $blockId }}" class="block-section container-shop py-6">
        <div @class(['flex flex-wrap gap-3', 'justify-center' => ($block['align'] ?? 'left') === 'center', 'justify-end' => ($block['align'] ?? 'left') === 'right'])>
            @foreach ($block['items'] as $button)
                @if (! empty($button['label']) && ! empty($button['url']))
                    <a href="{{ $button['url'] }}" @class(['btn', 'btn-primary' => ($button['style'] ?? 'primary') === 'primary', 'btn-secondary' => ($button['style'] ?? 'primary') !== 'primary'])>{{ $button['label'] }}</a>
                @endif
            @endforeach
        </div>
    </div>
@endif
