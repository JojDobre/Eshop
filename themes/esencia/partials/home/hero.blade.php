{{-- Úvod: farebný banner s nadpisom, tlačidlami, fotkou a nálepkou, vedľa dve menšie akcie. --}}
@php
    $heroLink = trim((string) $themeOptions->get('hero_link')) ?: (! empty($homeCategories) && $homeCategories->isNotEmpty() ? $homeCategories->first()->url() : null);
    $heroImage = $themeOptions->imageUrl('hero_image');
    $heroBadge = trim((string) $themeOptions->get('hero_badge'));
    $heroSticker = trim((string) $themeOptions->get('hero_sticker'));
    $promos = collect([1, 2])->map(fn ($i) => [
        'label' => trim((string) $themeOptions->get("promo_{$i}_label")), 'title' => trim((string) $themeOptions->get("promo_{$i}_title")),
        'link_text' => trim((string) $themeOptions->get("promo_{$i}_link_text")), 'link' => trim((string) $themeOptions->get("promo_{$i}_link")),
        'image' => $themeOptions->imageUrl("promo_{$i}_image"), 'dark' => $i === 2,
    ])->filter(fn ($promo) => $promo['title'] !== '');
@endphp
<section @class([$wrap, 'grid gap-4 pt-6 sm:pt-8', 'lg:grid-cols-[1.6fr_1fr]' => $promos->isNotEmpty()]) aria-labelledby="home-hero">
    <div @class(['relative overflow-hidden rounded-[2rem] bg-ac text-onac', 'lg:order-2' => $reverse])>
        <div @class(['grid items-stretch', 'md:grid-cols-[1.1fr_1fr]' => $heroImage])>
            <div class="flex flex-col justify-center p-8 sm:p-12">
                @if ($heroBadge !== '')
                    <span class="es-sticker w-fit rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wider">{{ $heroBadge }}</span>
                @endif
                <h1 id="home-hero" class="mt-5 text-[2.6rem] leading-[0.95] sm:text-[4rem]">{{ $opt('hero_title') }}</h1>
                <p class="mt-5 max-w-sm text-[17px] font-medium">{{ $opt('hero_text') }}</p>
                <div class="mt-7 flex flex-wrap gap-3">
                    @if ($heroLink)
                        <a href="{{ $heroLink }}" class="btn btn-ink">{{ $opt('hero_button') }}</a>
                    @endif
                    @if (filled($themeOptions->get('hero_button_2')) && filled($themeOptions->get('hero_link_2')))
                        <a href="{{ $themeOptions->get('hero_link_2') }}" class="btn border-2 border-current">{{ $themeOptions->get('hero_button_2') }}</a>
                    @endif
                </div>
            </div>
            @if ($heroImage)
                <div class="relative min-h-[20rem]">
                    <img {{ responsive_img($heroImage, '(min-width: 1024px) 30vw, 95vw') }} alt="" class="absolute inset-0 size-full object-cover" fetchpriority="high">
                    @if ($heroSticker !== '')
                        <span class="es-sticker absolute left-6 top-6 grid size-24 -rotate-12 place-items-center rounded-full p-3 text-center font-head text-lg leading-tight shadow-xl [text-wrap:balance]">{{ $heroSticker }}</span>
                    @endif
                </div>
            @endif
        </div>
    </div>

    @if ($promos->isNotEmpty())
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
            @foreach ($promos as $promo)
                <a href="{{ $promo['link'] ?: '#' }}" @class(['relative flex min-h-52 flex-col overflow-hidden rounded-[2rem] p-7', 'bg-t3' => ! $promo['dark'], 'bg-ink text-white' => $promo['dark']])>
                    @if ($promo['label'] !== '')
                        <span @class(['text-xs font-bold uppercase tracking-wider', 'text-muted' => ! $promo['dark'], 'text-white/70' => $promo['dark']])>{{ $promo['label'] }}</span>
                    @endif
                    <span class="mt-2 max-w-[60%] font-head text-2xl leading-tight">{{ $promo['title'] }}</span>
                    @if ($promo['link_text'] !== '')
                        <span class="mt-auto pt-4 font-semibold underline decoration-2 underline-offset-4">{{ $promo['link_text'] }}</span>
                    @endif
                    @if ($promo['image'])
                        <img {{ responsive_img($promo['image'], '200px') }} alt="" @class(['absolute -bottom-4 -right-6 w-1/2 rounded-2xl shadow-xl', 'rotate-6' => ! $promo['dark'], '-rotate-6' => $promo['dark']]) loading="lazy">
                    @endif
                </a>
            @endforeach
        </div>
    @endif
</section>
