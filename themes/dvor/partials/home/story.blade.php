{{-- Náš príbeh: text a dve recenzie, vedľa veľká fotka v tvare vajíčka s menšou fotkou. --}}
@php
    $storyImage = $themeOptions->imageUrl('story_image');
    $storyImage2 = $themeOptions->imageUrl('story_image_2');
    $reviews = collect([1, 2])->map(fn ($i) => ['text' => trim((string) $themeOptions->get('review_'.$i.'_text')), 'author' => trim((string) $themeOptions->get('review_'.$i.'_author'))])
        ->filter(fn ($review) => $review['text'] !== '');
@endphp
<section @class([$wrap, 'grid items-center gap-10 pb-16', 'lg:grid-cols-2' => $storyImage]) aria-labelledby="home-story">
    <div @class(['min-w-0', 'lg:order-2' => $reverse && $storyImage])>
        <p class="dv-kicker !text-[var(--dv-actext)]">{{ $opt('story_kicker') }}</p>
        <h2 id="home-story" class="dv-h2 mt-2">{{ $opt('story_title') }}</h2>
        <p class="mt-4 max-w-lg text-muted">{{ $opt('story_text') }}</p>
        @if ($reviews->isNotEmpty())
            <div class="mt-8 grid gap-4 sm:grid-cols-2">
                @foreach ($reviews as $review)
                    <figure class="dv-card p-5">
                        <blockquote class="text-[15px] leading-snug">„{{ $review['text'] }}“</blockquote>
                        @if ($review['author'] !== '')
                            <figcaption class="mt-3 flex items-center gap-2 text-sm font-bold"><span class="grid size-8 shrink-0 place-items-center rounded-full bg-sun text-onsun" aria-hidden="true">{{ mb_substr($review['author'], 0, 1) }}</span>{{ $review['author'] }}</figcaption>
                        @endif
                    </figure>
                @endforeach
            </div>
        @endif
    </div>
    @if ($storyImage)
        <div @class(['relative mx-auto w-full max-w-[460px]', 'lg:order-1' => $reverse])>
            <img {{ responsive_img($storyImage, '(min-width: 1024px) 32vw, 90vw') }} alt="" loading="lazy" class="egg aspect-[5/6] w-full object-cover">
            @if ($storyImage2)
                <img {{ responsive_img($storyImage2, '160px') }} alt="" loading="lazy" class="egg absolute -bottom-4 left-0 aspect-[4/5] w-[34%] object-cover ring-8 ring-[var(--dv-bg)] sm:-left-6">
            @endif
        </div>
    @endif
</section>
