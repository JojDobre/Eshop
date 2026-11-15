@extends('theme::layouts.app')

{{--
    Úvodná stránka Štúdia: sekcie v poradí a s rozložením podľa nastavenia „Sekcie úvodnej stránky“
    (Vzhľad → Prispôsobiť). Prázdne texty v Prispôsobení sa nahradia predvolenými textami šablóny v jazyku obchodu.
--}}
@php
    $homeBlocks = \App\Models\BlockLayout::blocksFor(\App\Models\BlockLayout::HOME);
    $homeSections = $themeOptions->get('home_sections', []);
    $opt = fn (string $key) => trim((string) $themeOptions->get($key)) ?: __('theme::studio.'.$key);
    $bookingServices = Route::has('reservations.index')
        ? rescue(fn () => \Modules\Reservations\Models\BookingService::query()->active()->get(), collect(), false)
        : collect();
@endphp

@push('head')
    <script type="application/ld+json">{!! json_encode([
        '@'.'context' => 'https://schema.org',
        '@graph' => array_values(array_filter([
            array_filter(['@type' => 'LocalBusiness', 'name' => $company->company_name ?: $shop->shop_name, 'url' => route('storefront.home'), 'email' => $shop->contact_email, 'telephone' => $shop->contact_phone,
                'address' => trim((string) $themeOptions->get('contact_address')) ?: null]),
            ['@type' => 'WebSite', 'name' => $shop->shop_name, 'url' => route('storefront.home')],
        ])),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
    @foreach ($homeSections as $homeSection)
        @php
            $layout = $homeSection['layout'] ?? 'default';
            $wrap = match ($layout) {
                'full' => 'w-full px-4 sm:px-6',
                'narrow' => 'container-shop max-w-4xl',
                default => 'container-shop',
            };
        @endphp
        @if (view()->exists('theme::partials.home.'.$homeSection['section']))
            @include('theme::partials.home.'.$homeSection['section'], ['layout' => $layout, 'wrap' => $wrap, 'reverse' => $layout === 'reverse', 'opt' => $opt, 'homeBlocks' => $homeBlocks, 'bookingServices' => $bookingServices])
        @endif
    @endforeach

    @if ($homeSections === [])
        <section class="container-shop py-16 text-muted">{{ __('theme::shop.coming_soon') }}</section>
    @endif
@endsection
