{{-- Príležitosti: vysoké fotky v tvare lupeňa s názvom (na mobile posúvanie do strany). --}}
@php
    $occasions = collect(range(1, 6))->map(fn ($i) => [
        'title' => trim((string) $themeOptions->get('occasion_'.$i)), 'link' => trim((string) $themeOptions->get('occasion_'.$i.'_link')), 'image' => $themeOptions->imageUrl('occasion_'.$i.'_image'),
    ])->filter(fn ($occasion) => $occasion['title'] !== '' && $occasion['image'])->values();
@endphp
@if ($occasions->isNotEmpty())
    <section class="{{ $wrap }} pb-10 pt-16 sm:pt-20" aria-labelledby="home-occasions">
        <div class="flex items-end justify-between gap-4">
            <h2 id="home-occasions" class="lp-h2">{{ $opt('occasions_title') }}</h2>
            @if (! empty($homeCategories) && $homeCategories->isNotEmpty())
                <a href="{{ $homeCategories->first()->url() }}" class="lp-link hidden shrink-0 sm:inline">{{ __('theme::lupen.all_bouquets') }}</a>
            @endif
        </div>
        <div @class(['-mx-4 mt-8 flex snap-x gap-4 overflow-x-auto px-4 pb-2 sm:mx-0 sm:px-0 lg:grid lg:overflow-visible', 'lg:grid-cols-6' => $occasions->count() >= 6, 'lg:grid-cols-4' => $occasions->count() < 6])>
            @foreach ($occasions as $occasion)
                <a href="{{ $occasion['link'] ?: '#' }}" @class(['lp-zoom group relative aspect-[3/4] w-40 shrink-0 snap-start overflow-hidden sm:w-44 lg:w-auto', 'leaf-sm' => $loop->odd, 'leaf-sm-r' => $loop->even])>
                    <img {{ responsive_img($occasion['image'], '(min-width: 1024px) 16vw, 45vw') }} alt="" class="absolute inset-0 size-full object-cover" loading="lazy">
                    <span class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/0"></span>
                    <span class="absolute inset-x-4 bottom-4 font-head text-xl leading-tight text-white">{{ $occasion['title'] }}</span>
                </a>
            @endforeach
        </div>
    </section>
@endif
