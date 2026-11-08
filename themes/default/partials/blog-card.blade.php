<article class="group relative flex flex-col">
    <div class="aspect-[16/10] overflow-hidden rounded-xl bg-zinc-100">
        @if ($cover = $post->coverUrl())
            <img src="{{ $cover }}" alt="" loading="lazy" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]">
        @endif
    </div>
    <div class="mt-4 flex flex-1 flex-col">
        <p class="text-xs text-zinc-500">
            {{ $post->published_at?->format(app()->getLocale() === 'en' ? 'j M Y' : 'j. n. Y') }}
            · {{ trans_choice('theme::blog.reading_time', $post->readingMinutes(), ['count' => $post->readingMinutes()]) }}
        </p>
        <h3 class="mt-1 text-lg font-semibold tracking-tight">
            <a href="{{ $post->url() }}" class="after:absolute after:inset-0">{{ $post->name }}</a>
        </h3>
        @if ($post->excerpt)
            <p class="mt-2 line-clamp-3 text-sm text-zinc-600">{{ $post->excerpt }}</p>
        @endif
    </div>
</article>
