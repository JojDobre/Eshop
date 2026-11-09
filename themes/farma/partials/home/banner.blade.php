{{-- Baner: obrázok s textom, alebo iba obrázok (bez nadpisu a textu). --}}
@php
    $bannerImage = $themeOptions->imageUrl('banner_image');
    $bannerTitle = trim((string) $themeOptions->get('banner_title'));
    $bannerText = trim(strip_tags((string) $themeOptions->get('banner_text'))) !== '' ? $themeOptions->get('banner_text') : '';
    $bannerLink = $themeOptions->get('banner_link');
@endphp
@if ($bannerTitle !== '' || $bannerText !== '')
    <div class="{{ $wrap }} farma-section mt-5">
        <section @class(['grid overflow-hidden bg-cream', 'rounded-[28px]' => $layout !== 'full', 'lg:grid-cols-[1.4fr_1fr]' => $bannerImage && ! $reverse, 'lg:grid-cols-[1fr_1.4fr]' => $bannerImage && $reverse])>
            @if ($bannerImage)
                <img src="{{ $bannerImage }}" alt="" @class(['h-48 w-full object-cover sm:h-full sm:min-h-[340px]', 'lg:order-2' => $reverse]) loading="lazy">
            @endif
            <div class="self-center p-6 sm:p-12">
                @if ($bannerTitle !== '')
                    <h2 class="text-2xl font-black leading-tight sm:text-3xl">{{ $bannerTitle }}</h2>
                @endif
                @if ($bannerText !== '')
                    <div class="farma-rich mt-3 text-zinc-600">{{ safe_html($bannerText) }}</div>
                @endif
                @if (filled($bannerLink))
                    <a href="{{ $bannerLink }}" class="btn btn-primary farma-arrow mt-6">{{ $themeOptions->get('banner_button') ?: __('theme::farma.more') }} <span aria-hidden="true">→</span></a>
                @endif
            </div>
        </section>
    </div>
@elseif ($bannerImage)
    <div class="{{ $wrap }} farma-section mt-5">
        @php($bannerImg = '<img src="'.e($bannerImage).'" alt="'.e($themeOptions->get('banner_button') ?: $shop->shop_name).'" class="w-full object-cover" loading="lazy">')
        @if (filled($bannerLink))
            <a href="{{ $bannerLink }}" @class(['farma-zoom block overflow-hidden', 'rounded-[28px]' => $layout !== 'full'])>{!! $bannerImg !!}</a>
        @else
            <div @class(['overflow-hidden', 'rounded-[28px]' => $layout !== 'full'])>{!! $bannerImg !!}</div>
        @endif
    </div>
@endif
