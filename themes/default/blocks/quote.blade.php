<section id="{{ $blockId }}" class="block-section container-shop py-10">
    <figure class="mx-auto max-w-3xl border-l-4 border-primary pl-6">
        <blockquote class="text-xl italic text-zinc-800">„{{ $block['text'] ?? '' }}“</blockquote>
        @if (! empty($block['author']))
            <figcaption class="mt-3 text-sm text-zinc-500">— {{ $block['author'] }}</figcaption>
        @endif
    </figure>
</section>
