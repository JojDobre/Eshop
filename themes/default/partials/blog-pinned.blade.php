{{--
    Pripnutý článok (Blog → článok → Pripnúť na vrch blogu): veľký banner s titulnou fotkou, nadpisom, úvodom
    a tlačidlom. Bez fotky má banner farbu značky. Zobrazí sa len na prvej strane zoznamu článkov.
--}}
@php($pinnedCover = $pinnedPost->coverUrl())
<article @class(['blog-pinned group relative isolate flex min-h-[22rem] items-end overflow-hidden rounded-3xl sm:min-h-[26rem]', 'bg-primary text-primary-foreground' => ! $pinnedCover, 'bg-zinc-900 text-white' => $pinnedCover])>
    @if ($pinnedCover)
        <img {{ responsive_img($pinnedCover, '100vw') }} alt="" class="absolute inset-0 -z-10 h-full w-full object-cover transition duration-700 group-hover:scale-[1.02]" fetchpriority="high">
        <div class="absolute inset-0 -z-10 bg-gradient-to-t from-black/80 via-black/35 to-black/5"></div>
    @endif
    <div class="w-full max-w-3xl p-6 sm:p-10">
        <p class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold uppercase tracking-wide backdrop-blur">
            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 4h6l-1 5 3 3H7l3-3-1-5zM12 12v8" /></svg>
            {{ __('theme::blog.pinned') }}
        </p>
        <h2 class="blog-featured-title mt-4 text-3xl font-semibold leading-tight tracking-tight sm:text-5xl">
            <a href="{{ $pinnedPost->url() }}" class="after:absolute after:inset-0">{{ $pinnedPost->name }}</a>
        </h2>
        @if ($pinnedPost->excerpt)
            <p class="mt-3 line-clamp-3 max-w-2xl text-base opacity-90 sm:text-lg">{{ $pinnedPost->excerpt }}</p>
        @endif
        <p class="mt-6 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
            <span class="inline-flex items-center gap-2 rounded-full bg-white px-5 py-2.5 font-semibold text-zinc-900">{{ __('theme::blog.read_post') }} →</span>
            <span class="opacity-80">{{ $pinnedPost->published_at?->format(app()->getLocale() === 'en' ? 'j M Y' : 'j. n. Y') }} · {{ trans_choice('theme::blog.reading_time', $pinnedPost->readingMinutes(), ['count' => $pinnedPost->readingMinutes()]) }}</span>
        </p>
    </div>
</article>
