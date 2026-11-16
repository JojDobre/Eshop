{{--
    Banner s obrázkom (Vzhľad stránky → Banner s obrázkom). Parametre: $heroTitle, $heroText, $heroImage, $heroHeight
    a $heroImageOnly – samotný obrázok bez nadpisu a stmavenia (nadpis zostane len pre čítačky a vyhľadávače).
--}}
@php($heroImageOnly = ($heroImageOnly ?? false) && ! empty($heroImage))
<section @class([
    'page-hero relative isolate flex items-end overflow-hidden',
    'bg-zinc-900 text-white' => ! $heroImageOnly,
    'page-hero-image' => $heroImageOnly,
    'min-h-[14rem] sm:min-h-[18rem]' => ($heroHeight ?? 'medium') === 'small',
    'min-h-[18rem] sm:min-h-[26rem]' => ($heroHeight ?? 'medium') === 'medium',
    'min-h-[24rem] sm:min-h-[36rem]' => ($heroHeight ?? 'medium') === 'large',
])>
    @if (! empty($heroImage))
        <img {{ responsive_img($heroImage, '100vw') }} alt="" class="absolute inset-0 -z-10 h-full w-full object-cover" fetchpriority="high">
        @unless ($heroImageOnly)
            <div class="absolute inset-0 -z-10 bg-gradient-to-t from-black/70 via-black/25 to-transparent"></div>
        @endunless
    @endif
    @if ($heroImageOnly)
        <h1 class="sr-only">{{ $heroTitle }}</h1>
    @else
        <div class="container-shop w-full py-10 sm:py-14">
            <h1 class="page-hero-title max-w-3xl text-3xl font-semibold tracking-tight sm:text-5xl">{{ $heroTitle }}</h1>
            @if (! empty($heroText))
                <p class="mt-4 max-w-2xl whitespace-pre-line text-lg text-white/90">{{ $heroText }}</p>
            @endif
        </div>
    @endif
</section>
