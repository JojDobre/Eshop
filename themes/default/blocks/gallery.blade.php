@if (! empty($block['images']))
    <section id="{{ $blockId }}" class="block-section container-shop py-10">
        @if (! empty($block['title']))
            <h2 class="block-title mb-6 text-2xl font-semibold tracking-tight">{{ $block['title'] }}</h2>
        @endif
        <div @class(['grid gap-3', 'grid-cols-2' => ($block['columns'] ?? '3') === '2', 'grid-cols-2 sm:grid-cols-3' => ($block['columns'] ?? '3') === '3', 'grid-cols-2 sm:grid-cols-4' => ($block['columns'] ?? '3') === '4'])>
            @foreach ((array) $block['images'] as $galleryImage)
                <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($galleryImage) }}" target="_blank" rel="noopener" class="block aspect-square overflow-hidden rounded-xl bg-zinc-100">
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($galleryImage) }}" alt="{{ $block['title'] ?? '' }}" loading="lazy" class="h-full w-full object-cover">
                </a>
            @endforeach
        </div>
    </section>
@endif
