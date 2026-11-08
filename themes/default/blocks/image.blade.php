@if (! empty($block['image']))
    @php($imageUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($block['image']))
    <section id="{{ $blockId }}" @class(['py-8', 'container-shop' => empty($block['full_width'])])>
        @if (! empty($block['url']))<a href="{{ $block['url'] }}" class="block">@endif
            <img src="{{ $imageUrl }}" alt="{{ $block['alt'] ?? '' }}" loading="lazy" @class(['w-full object-cover', 'rounded-2xl' => empty($block['full_width'])])>
        @if (! empty($block['url']))</a>@endif
    </section>
@endif
