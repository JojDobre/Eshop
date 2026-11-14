@php($headingAlign = ['left' => 'text-left', 'center' => 'text-center mx-auto', 'right' => 'text-right ml-auto'][$block['align'] ?? 'left'] ?? 'text-left')
<section id="{{ $blockId }}" class="block-section container-shop py-8">
    <div class="max-w-3xl {{ $headingAlign }}">
        <h2 @class([
            'block-title font-semibold tracking-tight',
            'text-xl' => ($block['size'] ?? 'medium') === 'small',
            'text-2xl sm:text-3xl' => ($block['size'] ?? 'medium') === 'medium',
            'text-3xl sm:text-5xl' => ($block['size'] ?? 'medium') === 'large',
        ])>{{ $block['title'] ?? '' }}</h2>
        @if (! empty($block['subtitle']))
            <p class="block-subtitle mt-3 whitespace-pre-line text-lg opacity-80">{{ $block['subtitle'] }}</p>
        @endif
    </div>
</section>
