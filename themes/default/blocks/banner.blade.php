{{-- Banner: nadpis, text, tlačidlo a obrázok na pozadí. --}}
@php($bannerImage = ! empty($block['image']) ? \Illuminate\Support\Facades\Storage::disk('public')->url($block['image']) : null)
<section id="{{ $blockId }}" @class([
    'relative overflow-hidden border-b border-zinc-100',
    'bg-gradient-to-b from-zinc-50 to-white' => ! $bannerImage,
    'bg-zinc-900 text-white' => $bannerImage,
])>
    @if ($bannerImage)
        <img src="{{ $bannerImage }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-60">
    @endif
    <div @class([
        'container-shop relative',
        'py-12 sm:py-16' => ($block['height'] ?? 'medium') === 'small',
        'py-20 sm:py-28' => ($block['height'] ?? 'medium') === 'medium',
        'py-28 sm:py-40' => ($block['height'] ?? 'medium') === 'large',
        'text-center' => ($block['align'] ?? 'left') === 'center',
    ])>
        <div @class(['max-w-2xl', 'mx-auto' => ($block['align'] ?? 'left') === 'center'])>
            <h2 class="block-title text-4xl font-semibold tracking-tight sm:text-5xl">{{ $block['title'] ?? '' }}</h2>
            @if (! empty($block['text']))
                <p @class(['mt-5 text-lg', 'text-zinc-600' => ! $bannerImage, 'text-white/90' => $bannerImage])>{{ $block['text'] }}</p>
            @endif
            @if (! empty($block['button_label']) && ! empty($block['button_url']))
                <a href="{{ $block['button_url'] }}" class="btn btn-primary mt-8">{{ $block['button_label'] }}</a>
            @endif
        </div>
    </div>
</section>
