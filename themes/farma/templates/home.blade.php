@extends('theme::layouts.app')

{{--
    Úvodná stránka Farmy: sekcie v poradí a s rozložením podľa nastavenia „Sekcie úvodnej stránky“
    (Vzhľad → Prispôsobiť). Sekcia „Bloky“ vloží bloky z Obsah → Úvodná stránka a bloky.
--}}
@php
    $faqItems = collect(range(1, 4))
        ->map(fn ($i) => ['q' => trim((string) $themeOptions->get('faq_'.$i.'_q')), 'a' => trim((string) $themeOptions->get('faq_'.$i.'_a'))])
        ->filter(fn ($item) => $item['q'] !== '' && trim(strip_tags($item['a'])) !== '')
        ->values();

    $homeBlocks = \App\Models\BlockLayout::blocksFor(\App\Models\BlockLayout::HOME);
    $homeSections = $themeOptions->get('home_sections', []);

    // Obchod s vlastnými blokmi a bez nastaveného poradia sekcií vyzerá ako doteraz: iba bloky.
    if (! empty($homeBlocks) && ! $themeOptions->isCustomized('home_sections')) {
        $homeSections = [['section' => 'blocks', 'layout' => 'default']];
    }

    $homeReviews = collect();
    if (collect($homeSections)->contains('section', 'reviews') && $themeOptions->get('show_reviews', true) && class_exists(\Modules\Reviews\Models\Review::class) && ! empty($reviewsEnabled)) {
        $homeReviews = \Modules\Reviews\Models\Review::query()->approved()->where('rating', '>=', 4)->whereNotNull('body')->latest()->limit(3)->get();
    }
@endphp

@push('head')
    <script type="application/ld+json">{!! json_encode(array_filter([
        '@'.'context' => 'https://schema.org',
        '@graph' => array_values(array_filter([
            array_filter(['@type' => 'Organization', 'name' => $company->company_name ?: $shop->shop_name, 'url' => route('storefront.home'), 'email' => $shop->contact_email, 'telephone' => $shop->contact_phone]),
            ['@type' => 'WebSite', 'name' => $shop->shop_name, 'url' => route('storefront.home'), 'potentialAction' => Route::has('catalog.search') ? [
                '@type' => 'SearchAction', 'target' => route('catalog.search').'?q={search_term_string}', 'query-input' => 'required name=search_term_string',
            ] : null],
            $faqItems->isNotEmpty() && collect($homeSections)->contains('section', 'faq') ? ['@type' => 'FAQPage', 'mainEntity' => $faqItems->map(fn ($item) => ['@type' => 'Question', 'name' => $item['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => trim(strip_tags($item['a']))]])->all()] : null,
        ])),
    ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
    @foreach ($homeSections as $homeSection)
        @php
            $layout = $homeSection['layout'] ?? 'default';
            $wrap = match ($layout) {
                'full' => 'w-full',
                'narrow' => 'container-shop max-w-4xl',
                default => 'container-shop',
            };
        @endphp
        @if (view()->exists('theme::partials.home.'.$homeSection['section']))
            @include('theme::partials.home.'.$homeSection['section'], ['layout' => $layout, 'wrap' => $wrap, 'reverse' => $layout === 'reverse'])
        @endif
    @endforeach

    @if ($homeSections === [])
        <section class="container-shop py-16 text-zinc-600">{{ __('theme::shop.coming_soon') }}</section>
    @endif
@endsection
