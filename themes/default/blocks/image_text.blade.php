@php($imageTextImage = ! empty($block['image']) && is_string($block['image']) ? \Illuminate\Support\Facades\Storage::disk('public')->url($block['image']) : null)
<section id="{{ $blockId }}" class="block-section container-shop py-12">
    <div class="grid items-center gap-8 md:grid-cols-2 md:gap-12">
        @if ($imageTextImage)
            <img src="{{ $imageTextImage }}" alt="{{ $block['title'] ?? '' }}" loading="lazy" @class(['w-full rounded-2xl object-cover', 'md:order-2' => ($block['image_side'] ?? 'left') === 'right'])>
        @endif
        <div>
            @if (! empty($block['title']))
                <h2 class="block-title text-2xl font-semibold tracking-tight sm:text-3xl">{{ $block['title'] }}</h2>
            @endif
            @if (! empty($block['content']))
                <div class="prose prose-zinc mt-4 max-w-none">{{ safe_html($block['content']) }}</div>
            @endif
            @if (! empty($block['button_label']) && ! empty($block['button_url']))
                <a href="{{ $block['button_url'] }}" class="btn btn-primary mt-6">{{ $block['button_label'] }}</a>
            @endif
        </div>
    </div>
</section>
