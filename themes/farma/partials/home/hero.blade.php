{{-- Úvodný baner (obrázok s odkazom) alebo názov obchodu. --}}
<div class="{{ $wrap }} farma-section">
    @if ($heroImage = $themeOptions->imageUrl('hero_image'))
        @php($heroImg = '<img src="'.e($heroImage).'" alt="'.e($shop->shop_name).'" class="h-52 w-full object-cover sm:h-[460px]" fetchpriority="high">')
        @php($heroClass = 'mt-4 block overflow-hidden sm:mt-6 '.($layout === 'full' ? '' : 'rounded-[20px] sm:rounded-[28px]'))
        @if (filled($themeOptions->get('hero_link')))
            <a href="{{ $themeOptions->get('hero_link') }}" class="{{ $heroClass }} farma-zoom">{!! $heroImg !!}</a>
        @else
            <div class="{{ $heroClass }}">{!! $heroImg !!}</div>
        @endif
    @else
        <section @class(['mt-4 bg-cream px-6 py-14 text-center sm:mt-6 sm:py-24', 'rounded-[28px]' => $layout !== 'full'])>
            <h1 class="text-3xl font-black tracking-tight sm:text-5xl">{{ $shop->shop_name }}</h1>
            @if ($shop->tagline)
                <p class="mx-auto mt-4 max-w-xl text-lg text-zinc-600">{{ $shop->tagline }}</p>
            @endif
        </section>
    @endif
</div>
