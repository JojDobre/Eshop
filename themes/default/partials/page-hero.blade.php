{{-- Banner s obrázkom a nadpisom na ňom (Vzhľad stránky → Banner s obrázkom). Parametre: $heroTitle, $heroText, $heroImage, $heroHeight. --}}
<section @class([
    'page-hero relative isolate flex items-end overflow-hidden bg-zinc-900 text-white',
    'min-h-[14rem] sm:min-h-[18rem]' => ($heroHeight ?? 'medium') === 'small',
    'min-h-[18rem] sm:min-h-[26rem]' => ($heroHeight ?? 'medium') === 'medium',
    'min-h-[24rem] sm:min-h-[36rem]' => ($heroHeight ?? 'medium') === 'large',
])>
    @if (! empty($heroImage))
        <img src="{{ $heroImage }}" alt="" class="absolute inset-0 -z-10 h-full w-full object-cover" fetchpriority="high">
        <div class="absolute inset-0 -z-10 bg-gradient-to-t from-black/70 via-black/25 to-transparent"></div>
    @endif
    <div class="container-shop w-full py-10 sm:py-14">
        <h1 class="page-hero-title max-w-3xl text-3xl font-semibold tracking-tight sm:text-5xl">{{ $heroTitle }}</h1>
        @if (! empty($heroText))
            <p class="mt-4 max-w-2xl whitespace-pre-line text-lg text-white/90">{{ $heroText }}</p>
        @endif
    </div>
</section>
