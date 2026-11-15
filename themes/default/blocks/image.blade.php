@if (! empty($block['image']))
    @php($imageUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($block['image']))
    <section id="{{ $blockId }}" @class(['py-8', 'container-shop' => empty($block['full_width'])])>
        @if (! empty($block['url']))<a href="{{ $block['url'] }}" class="block">@endif
            <img {{ responsive_img($imageUrl, empty($block['full_width']) ? '(min-width: 1280px) 80rem, 100vw' : '100vw') }} alt="{{ $block['alt'] ?? '' }}" loading="lazy" @class(['w-full object-cover', 'rounded-2xl' => empty($block['full_width'])])>
        @if (! empty($block['url']))</a>@endif
    </section>
@endif
