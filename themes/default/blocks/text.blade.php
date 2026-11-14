<section id="{{ $blockId }}" class="block-section container-shop py-12">
    <div class="mx-auto max-w-3xl">
        @if (! empty($block['title']))
            <h2 class="block-title text-2xl font-semibold tracking-tight">{{ $block['title'] }}</h2>
        @endif
        {{-- Text z editora administrácie (bezpečné značky z RichEditora). --}}
        <div class="prose prose-zinc mt-4 max-w-none">{{ safe_html($block['content'] ?? '') }}</div>
    </div>
</section>
