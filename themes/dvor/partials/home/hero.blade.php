{{--
    Úvod: štítok, nadpis (slovo v *hviezdičkách* je farebné), text, dve tlačidlá a čísla farmy; vpravo dve fotky
    v tvare vajíčka a lístok s krátkou správou. Lístok je vo vnútri fotky, aby na mobile nič nepresahovalo.
--}}
@php
    $heroImage = $themeOptions->imageUrl('hero_image');
    $heroImage2 = $themeOptions->imageUrl('hero_image_2');
    $heroTitle = preg_replace('/\*(.+?)\*/u', '<span class="text-ac">$1</span>', e($opt('hero_title')));
    $heroLink = trim((string) $themeOptions->get('hero_link')) ?: (! empty($homeCategories) && $homeCategories->isNotEmpty() ? $homeCategories->first()->url() : null);
    $heroLink2 = trim((string) $themeOptions->get('hero_link_2'));
    $stats = collect(preg_split('/\R/', (string) $themeOptions->get('hero_stats')))
        ->map(fn ($line) => array_map('trim', explode('|', $line, 2)))
        ->filter(fn ($parts) => $parts[0] !== '')->take(3);
    $noteTitle = trim((string) $themeOptions->get('hero_note_title'));
    $noteText = trim((string) $themeOptions->get('hero_note_text'));
@endphp
<section class="{{ $wrap }} grid items-center gap-10 pb-14 pt-10 sm:pt-14 @if ($heroImage) lg:grid-cols-[1.05fr_1fr] @endif" aria-labelledby="home-hero">
    <div @class(['min-w-0', 'lg:order-2' => $reverse && $heroImage])>
        <span class="inline-flex items-center gap-2 rounded-full bg-soft px-3.5 py-1.5 text-sm font-bold text-ac">@include('theme::partials.icon', ['name' => 'leaf', 'class' => 'size-4']){{ $opt('hero_badge') }}</span>
        <h1 id="home-hero" class="mt-5 text-[2.8rem] leading-[1.02] [text-wrap:balance] sm:text-7xl">{!! $heroTitle !!}</h1>
        <p class="mt-5 max-w-lg text-lg text-muted">{{ $opt('hero_text') }}</p>
        <div class="mt-7 flex flex-wrap gap-3">
            @if ($heroLink)<a href="{{ $heroLink }}" class="btn btn-ac">{{ $opt('hero_button') }}</a>@endif
            @if ($heroLink2 !== '')<a href="{{ $heroLink2 }}" class="btn btn-sun">@include('theme::partials.icon', ['name' => 'barn', 'class' => 'size-4']){{ $opt('hero_button_2') }}</a>@endif
        </div>
        @if ($stats->isNotEmpty())
            <dl class="mt-8 flex flex-wrap gap-x-8 gap-y-4 text-sm">
                @foreach ($stats as $stat)
                    <div class="flex flex-col-reverse"><dt class="text-muted">{{ $stat[1] ?? '' }}</dt><dd class="font-head text-3xl">{{ $stat[0] }}</dd></div>
                @endforeach
            </dl>
        @endif
    </div>

    @if ($heroImage)
        <div @class(['relative mx-auto w-full max-w-[540px]', 'lg:order-1' => $reverse])>
            <img {{ responsive_img($heroImage, '(min-width: 1024px) 34vw, 78vw') }} alt="" class="egg aspect-[4/5] w-[78%] object-cover shadow-2xl" fetchpriority="high">
            @if ($heroImage2)
                <img {{ responsive_img($heroImage2, '(min-width: 1024px) 18vw, 42vw') }} alt="" class="egg absolute right-0 top-[18%] aspect-[4/5] w-[42%] object-cover ring-8 ring-[var(--dv-bg)]">
            @endif
            @if ($noteTitle !== '')
                <div class="ticket absolute bottom-3 left-2 right-[6%] bg-card px-6 py-4 shadow-xl sm:left-6 sm:right-[30%]">
                    <p class="flex items-center gap-2 text-sm font-extrabold">@include('theme::partials.icon', ['name' => 'egg', 'class' => 'size-4 shrink-0 text-ac']){{ $noteTitle }}</p>
                    @if ($noteText !== '')<p class="text-xs text-muted">{{ $noteText }}</p>@endif
                </div>
            @endif
        </div>
    @endif
</section>
