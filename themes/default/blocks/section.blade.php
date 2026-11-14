{{-- Sekcia so stĺpcami: pozadie, šírka, odsadenie a v každom stĺpci ďalšie bloky. --}}
@php
    $sectionColumns = \App\Core\Blocks\CoreBlocks::SECTION_LAYOUTS[$block['layout'] ?? 'halves'] ?? [6, 6];
    $sectionBackground = $block['background'] ?? 'none';
    $sectionImage = $sectionBackground === 'image' && ! empty($block['background_image']) && is_string($block['background_image'])
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($block['background_image']) : null;
    $sectionDark = in_array($sectionBackground, ['dark', 'brand'], true) || $sectionImage;
    $spanClasses = [12 => 'md:col-span-12', 8 => 'md:col-span-8', 6 => 'md:col-span-6', 4 => 'md:col-span-4', 3 => 'md:col-span-6 lg:col-span-3'];
@endphp
<section id="{{ $blockId }}" @class([
    'block-grid-section relative isolate overflow-hidden',
    'bg-zinc-50 section-bg-light' => $sectionBackground === 'light',
    'bg-primary text-primary-foreground section-bg-brand' => $sectionBackground === 'brand',
    'bg-zinc-900 text-white section-bg-dark' => $sectionBackground === 'dark' || $sectionImage,
    'is-dark' => $sectionDark,
    'py-0' => ($block['padding'] ?? 'medium') === 'none',
    'py-8' => ($block['padding'] ?? 'medium') === 'small',
    'py-14 sm:py-16' => ($block['padding'] ?? 'medium') === 'medium',
    'py-20 sm:py-28' => ($block['padding'] ?? 'medium') === 'large',
])>
    @if ($sectionImage)
        <img src="{{ $sectionImage }}" alt="" class="absolute inset-0 -z-10 h-full w-full object-cover opacity-50" loading="lazy">
    @endif
    <div @class([
        'grid grid-cols-1 gap-8 md:grid-cols-12',
        'container-shop' => ($block['width'] ?? 'contained') !== 'full',
        'max-w-4xl' => ($block['width'] ?? 'contained') === 'narrow',
        'px-4 sm:px-8' => ($block['width'] ?? 'contained') === 'full',
        'items-center' => ($block['align'] ?? 'start') === 'center',
    ])>
        @foreach ($sectionColumns as $index => $span)
            <div class="block-columns min-w-0 space-y-6 {{ $spanClasses[$span] ?? 'md:col-span-12' }}">
                {!! app(\App\Core\Blocks\BlockTypes::class)->render(is_array($block['column_'.($index + 1)] ?? null) ? array_values($block['column_'.($index + 1)]) : [], \Illuminate\Support\Str::after($blockId, 'block-').'-c'.($index + 1).'-') !!}
            </div>
        @endforeach
    </div>
</section>
