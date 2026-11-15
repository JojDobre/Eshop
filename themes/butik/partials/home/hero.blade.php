{{-- Úvod: veľká fotka s nadpisom a tlačidlami, vedľa dlaždica s fotkou a farebná dlaždica (napr. výpredaj). --}}
@php
    $lines = fn (string $text) => collect(explode('|', $text))->map(fn ($line) => e(trim($line)))->implode('<br>');
    $heroImage = $themeOptions->imageUrl('hero_image');
    $heroLink = trim((string) $themeOptions->get('hero_link_1')) ?: (! empty($homeCategories) && $homeCategories->isNotEmpty() ? $homeCategories->first()->url() : null);
    $tileTitle = trim((string) $themeOptions->get('tile_title'));
    $saleTitle = trim((string) $themeOptions->get('sale_title'));
    $hasSide = $tileTitle !== '' || $saleTitle !== '';
@endphp
<section @class(['grid', 'lg:grid-cols-2' => $hasSide]) aria-labelledby="home-hero">
    <div @class(['relative min-h-[70vh] bg-ink lg:min-h-[88vh]', 'lg:order-2' => $reverse])>
        @if ($heroImage)
            <img {{ responsive_img($heroImage, $hasSide ? '(min-width: 1024px) 50vw, 100vw' : '100vw') }} alt="" class="absolute inset-0 size-full object-cover" fetchpriority="high">
        @endif
        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/65 to-transparent p-6 text-white sm:p-10">
            <p class="cap">{{ $opt('hero_kicker') }}</p>
            <h1 id="home-hero" class="mt-3 text-[4rem] sm:text-[7rem] lg:text-[8.5rem]">{!! $lines($opt('hero_title')) !!}</h1>
            <div class="mt-6 flex flex-wrap gap-3">
                @if ($heroLink)
                    <a href="{{ $heroLink }}" class="btn btn-light">{{ $opt('hero_button_1') }}</a>
                @endif
                @if (filled($themeOptions->get('hero_button_2')) && filled($themeOptions->get('hero_link_2')))
                    <a href="{{ $themeOptions->get('hero_link_2') }}" class="btn btn-line text-white hover:bg-white hover:text-ink">{{ $themeOptions->get('hero_button_2') }}</a>
                @endif
            </div>
        </div>
    </div>

    @if ($hasSide)
        <div class="grid auto-rows-fr">
            @if ($tileTitle !== '')
                <a href="{{ trim((string) $themeOptions->get('tile_link')) ?: '#' }}" class="bt-zoom relative block min-h-[40vh] overflow-hidden bg-soft">
                    @if ($tileImage = $themeOptions->imageUrl('tile_image'))
                        <img {{ responsive_img($tileImage, '(min-width: 1024px) 50vw, 100vw') }} alt="" class="absolute inset-0 size-full object-cover object-top" loading="lazy">
                    @endif
                    <span class="absolute bottom-6 left-6 bg-white px-4 py-3">
                        @if (filled($themeOptions->get('tile_label')))<span class="cap block text-muted">{{ $themeOptions->get('tile_label') }}</span>@endif
                        <span class="font-head block text-3xl">{{ $tileTitle }}</span>
                    </span>
                </a>
            @endif
            @if ($saleTitle !== '')
                <div class="relative flex min-h-[40vh] flex-col justify-between gap-6 overflow-hidden bg-ac p-8 text-onac sm:p-10">
                    @if ($saleImage = $themeOptions->imageUrl('sale_image'))
                        <img {{ responsive_img($saleImage, '(min-width: 1024px) 25vw, 50vw') }} alt="" class="absolute bottom-0 right-0 h-full w-1/2 object-cover opacity-90 mix-blend-multiply" loading="lazy">
                    @endif
                    @if (filled($themeOptions->get('sale_label')))<p class="cap relative">{{ $themeOptions->get('sale_label') }}</p>@endif
                    <p class="font-head relative text-[5rem] sm:text-[7rem]">{{ $saleTitle }}</p>
                    @if (filled($themeOptions->get('sale_button')))
                        <a href="{{ trim((string) $themeOptions->get('sale_link')) ?: '#' }}" class="btn btn-ink relative w-fit">{{ $themeOptions->get('sale_button') }}</a>
                    @endif
                </div>
            @endif
        </div>
    @endif
</section>
