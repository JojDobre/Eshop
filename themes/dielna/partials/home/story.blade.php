{{-- Príbeh tvorcu: dve fotky, ručne písané oslovenie, text, podpis a tlačidlo. --}}
@php
    $storyImage = $themeOptions->imageUrl('story_image');
    $storyImage2 = $themeOptions->imageUrl('story_image_2');
    $storyText = trim(strip_tags((string) $themeOptions->get('story_text'))) !== '' ? $themeOptions->get('story_text') : __('theme::dielna.story_text');
@endphp
<section class="{{ $wrap }} grid items-center gap-10 py-16 sm:py-24 {{ $storyImage ? 'lg:grid-cols-2' : 'max-w-3xl' }}" aria-labelledby="home-story">
    @if ($storyImage)
        <div @class(['relative', 'lg:order-2' => $reverse])>
            <img {{ responsive_img($storyImage, '(min-width: 1024px) 40vw, 80vw') }} alt="" class="arch aspect-[4/5] w-4/5 object-cover" loading="lazy">
            @if ($storyImage2)
                <img {{ responsive_img($storyImage2, '(min-width: 1024px) 25vw, 50vw') }} alt="" class="absolute bottom-0 right-0 aspect-square w-1/2 rounded-2xl border-[6px] border-[var(--dielna-bg)] object-cover" loading="lazy">
            @endif
        </div>
    @endif
    <div>
        <p class="dielna-kicker">{{ $opt('story_kicker') }}</p>
        <h2 id="home-story" class="dielna-h2 mt-1">{{ $opt('story_title') }}</h2>
        <div class="prose prose-lg mt-5 max-w-none text-muted prose-li:my-1 prose-p:leading-relaxed prose-strong:text-ink">{{ safe_html($storyText) }}</div>
        @if (filled($themeOptions->get('story_signature')))
            <p class="mt-6 font-hand text-4xl text-accent">{{ $themeOptions->get('story_signature') }}</p>
        @endif
        @if (filled($themeOptions->get('story_link')))
            <a href="{{ $themeOptions->get('story_link') }}" class="btn btn-secondary mt-6">{{ $opt('story_button') }}</a>
        @endif
    </div>
</section>
