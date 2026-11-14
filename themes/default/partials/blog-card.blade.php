{{-- Karta článku. $cardLayout: grid (obrázok hore), list (obrázok vedľa textu), large (veľká karta). --}}
@php
    $cardLayout = $cardLayout ?? 'grid';
    $showMeta = $themeOptions->get('blog_show_meta', true);
    $showExcerpt = $themeOptions->get('blog_show_excerpt', true);
    $cover = $post->coverUrl();
@endphp
<article @class([
    'blog-card group relative',
    'flex flex-col' => $cardLayout !== 'list',
    'grid gap-5 sm:grid-cols-[minmax(0,2fr)_minmax(0,3fr)] sm:items-center' => $cardLayout === 'list',
])>
    @if ($cover || $cardLayout !== 'list')
        <div @class(['overflow-hidden rounded-xl bg-zinc-100', 'aspect-[16/10]' => $cardLayout !== 'large', 'aspect-[16/9]' => $cardLayout === 'large'])>
            @if ($cover)
                <img src="{{ $cover }}" alt="" loading="lazy" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]">
            @endif
        </div>
    @endif
    <div @class(['flex flex-1 flex-col', 'mt-4' => $cardLayout !== 'list'])>
        @if ($showMeta)
            <p class="text-xs text-zinc-500">
                {{ $post->published_at?->format(app()->getLocale() === 'en' ? 'j M Y' : 'j. n. Y') }}
                · {{ trans_choice('theme::blog.reading_time', $post->readingMinutes(), ['count' => $post->readingMinutes()]) }}
            </p>
        @endif
        <h3 @class(['blog-card-title mt-1 font-semibold tracking-tight', 'text-lg' => $cardLayout === 'grid', 'text-xl sm:text-2xl' => $cardLayout !== 'grid'])>
            <a href="{{ $post->url() }}" class="after:absolute after:inset-0">{{ $post->name }}</a>
        </h3>
        @if ($showExcerpt && $post->excerpt)
            <p @class(['mt-2 text-zinc-600', 'line-clamp-3 text-sm' => $cardLayout === 'grid', 'line-clamp-4' => $cardLayout !== 'grid'])>{{ $post->excerpt }}</p>
        @endif
    </div>
</article>
