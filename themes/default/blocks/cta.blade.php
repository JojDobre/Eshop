<section id="{{ $blockId }}" class="container-shop py-10">
    <div class="flex flex-col items-start justify-between gap-6 rounded-2xl bg-zinc-900 p-8 text-white sm:flex-row sm:items-center">
        <div>
            <h2 class="text-2xl font-semibold tracking-tight">{{ $block['title'] ?? '' }}</h2>
            @if (! empty($block['text']))
                <p class="mt-2 text-white/80">{{ $block['text'] }}</p>
            @endif
        </div>
        @if (! empty($block['button_url']))
            <a href="{{ $block['button_url'] }}" class="btn shrink-0 bg-white text-zinc-900 hover:bg-zinc-100">{{ $block['button_label'] ?? '' }}</a>
        @endif
    </div>
</section>
