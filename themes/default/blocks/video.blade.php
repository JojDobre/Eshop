@if (! empty($block['embed']))
    <section id="{{ $blockId }}" class="container-shop py-12">
        @if (! empty($block['title']))
            <h2 class="mb-6 text-2xl font-semibold tracking-tight">{{ $block['title'] }}</h2>
        @endif
        <div class="aspect-video overflow-hidden rounded-2xl bg-zinc-100">
            <iframe src="{{ $block['embed'] }}" title="{{ $block['title'] ?? __('theme::shop.video') }}" loading="lazy" class="h-full w-full"
                    allow="accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowfullscreen></iframe>
        </div>
    </section>
@endif
