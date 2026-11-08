@extends('theme::layouts.app')

@php
    $faqItems = collect(range(1, 4))
        ->map(fn ($i) => ['q' => trim((string) $themeOptions->get('faq_'.$i.'_q')), 'a' => trim((string) $themeOptions->get('faq_'.$i.'_a'))])
        ->filter(fn ($item) => $item['q'] !== '' && $item['a'] !== '')
        ->values();

    $homeReviews = collect();
    if ($themeOptions->get('show_reviews', true) && class_exists(\Modules\Reviews\Models\Review::class) && ! empty($reviewsEnabled)) {
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
            $faqItems->isNotEmpty() ? ['@type' => 'FAQPage', 'mainEntity' => $faqItems->map(fn ($item) => ['@type' => 'Question', 'name' => $item['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a']]])->all()] : null,
        ])),
    ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
    @if ($homeBlocks = \App\Models\BlockLayout::blocksFor(\App\Models\BlockLayout::HOME))
        {!! render_blocks($homeBlocks) !!}
    @else
        <div class="container-shop">
            {{-- Úvodný baner --}}
            @if ($heroImage = $themeOptions->imageUrl('hero_image'))
                @php($heroImg = '<img src="'.e($heroImage).'" alt="'.e($shop->shop_name).'" class="h-52 w-full object-cover sm:h-[460px]" fetchpriority="high">')
                @if (filled($themeOptions->get('hero_link')))
                    <a href="{{ $themeOptions->get('hero_link') }}" class="mt-4 block overflow-hidden rounded-[20px] sm:mt-6 sm:rounded-[28px]">{!! $heroImg !!}</a>
                @else
                    <div class="mt-4 overflow-hidden rounded-[20px] sm:mt-6 sm:rounded-[28px]">{!! $heroImg !!}</div>
                @endif
            @else
                <section class="mt-4 rounded-[28px] bg-cream px-6 py-14 text-center sm:mt-6 sm:py-24">
                    <h1 class="text-3xl font-black tracking-tight sm:text-5xl">{{ $shop->shop_name }}</h1>
                    @if ($shop->tagline)
                        <p class="mx-auto mt-4 max-w-xl text-lg text-zinc-600">{{ $shop->tagline }}</p>
                    @endif
                </section>
            @endif

            {{-- Kategórie v dlaždiciach --}}
            @if (! empty($homeCategories) && $homeCategories->isNotEmpty())
                <nav class="mt-3 grid grid-cols-2 gap-2.5 sm:mt-5 sm:gap-4 lg:grid-cols-4" aria-label="{{ __('theme::shop.categories') }}">
                    @foreach ($homeCategories->take(4) as $homeCategory)
                        <a href="{{ $homeCategory->url() }}" class="group flex h-26 justify-between overflow-hidden rounded-[20px] border border-transparent bg-cream transition hover:border-[var(--farma-cream-dark)] sm:h-32">
                            <span class="flex flex-col py-3.5 pl-4 sm:py-5 sm:pl-5">
                                <span class="font-black leading-tight sm:text-lg">{{ $homeCategory->name }}</span>
                                <span class="mt-auto grid size-7 place-items-center rounded-full bg-white text-primary transition group-hover:translate-x-1 sm:size-8">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6" /></svg>
                                </span>
                            </span>
                            @if ($categoryImage = $homeCategory->imageUrl())
                                <img src="{{ $categoryImage }}" alt="" class="w-[42%] object-cover [clip-path:ellipse(100%_95%_at_100%_50%)]" loading="lazy">
                            @endif
                        </a>
                    @endforeach
                </nav>
            @endif

            {{-- Produkty --}}
            @if (! empty($latestProducts) && $latestProducts->isNotEmpty())
                <section class="py-14 sm:py-20" aria-labelledby="home-products">
                    <div class="mb-6 flex items-end justify-between gap-4 sm:mb-8">
                        <h2 id="home-products" class="farma-h2">{{ $themeOptions->get('products_title') ?: __('theme::shop.latest_products') }}</h2>
                        @if (! empty($homeCategories) && $homeCategories->isNotEmpty())
                            <a href="{{ $homeCategories->first()->url() }}" class="farma-link text-sm sm:text-base">{{ __('theme::farma.show_all') }}</a>
                        @endif
                    </div>
                    @include('theme::partials.product-grid', ['products' => $latestProducts])
                </section>
            @else
                <section class="py-16 text-zinc-600">{{ __('theme::shop.coming_soon') }}</section>
            @endif

            {{-- Baner a pruh s tipom --}}
            @if (filled($themeOptions->get('banner_title')))
                <section class="grid overflow-hidden rounded-[28px] bg-cream lg:grid-cols-[1.4fr_1fr]">
                    @if ($bannerImage = $themeOptions->imageUrl('banner_image'))
                        <img src="{{ $bannerImage }}" alt="" class="h-48 w-full object-cover sm:h-full sm:min-h-[340px]" loading="lazy">
                    @endif
                    <div class="self-center p-6 sm:p-12">
                        <h2 class="text-2xl font-black leading-tight sm:text-3xl">{{ $themeOptions->get('banner_title') }}</h2>
                        @if (filled($themeOptions->get('banner_text')))
                            <p class="mt-3 text-zinc-600">{{ $themeOptions->get('banner_text') }}</p>
                        @endif
                        @if (filled($themeOptions->get('banner_link')))
                            <a href="{{ $themeOptions->get('banner_link') }}" class="btn btn-primary mt-6">{{ $themeOptions->get('banner_button') ?: __('theme::farma.more') }} →</a>
                        @endif
                    </div>
                </section>
            @endif

            @if (filled($themeOptions->get('strip_title')))
                <section class="mt-5 flex flex-wrap items-center gap-4 rounded-[20px] border border-line p-3.5 sm:flex-nowrap sm:gap-5 sm:pr-6">
                    @if ($stripImage = $themeOptions->imageUrl('strip_image'))
                        <img src="{{ $stripImage }}" alt="" class="w-18 rounded-xl sm:w-28" loading="lazy">
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="font-black">{{ $themeOptions->get('strip_title') }}</p>
                        @if (filled($themeOptions->get('strip_text')))
                            <p class="text-sm text-muted">{{ $themeOptions->get('strip_text') }}</p>
                        @endif
                    </div>
                    @if (filled($themeOptions->get('strip_link')))
                        <a href="{{ $themeOptions->get('strip_link') }}" class="btn btn-primary w-full py-2.5 sm:w-auto">{{ $themeOptions->get('strip_button') ?: __('theme::farma.more') }}</a>
                    @endif
                </section>
            @endif

            {{-- O nás a výhody --}}
            @if (filled($themeOptions->get('about_title')))
                <section class="grid items-center gap-8 py-14 sm:py-20 lg:grid-cols-2 lg:gap-16">
                    @if ($aboutImage = $themeOptions->imageUrl('about_image'))
                        <img src="{{ $aboutImage }}" alt="" class="w-full rounded-[28px] object-cover" loading="lazy">
                    @endif
                    <div>
                        <h2 class="farma-h2">{{ $themeOptions->get('about_title') }}</h2>
                        @if (filled($themeOptions->get('about_text')))
                            <p class="mt-4 text-lg text-zinc-600">{{ $themeOptions->get('about_text') }}</p>
                        @endif
                        @if ($themeOptions->get('show_benefits', true))
                            <ul class="mt-7 grid gap-x-7 gap-y-4 sm:grid-cols-2">
                                @foreach (['packing' => 'M3 7l9-4 9 4v10l-9 4-9-4zM3 7l9 4 9-4M12 11v10', 'fresh' => 'M12 3c4 0 7 7 7 11a7 7 0 0 1-14 0c0-4 3-11 7-11z', 'advice' => 'M4 5h16v11H8l-4 4z', 'own' => 'M3 11l9-7 9 7v9H3zM9 20v-6h6v6'] as $benefit => $icon)
                                    <li class="flex gap-3">
                                        <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-soft text-primary">
                                            <svg class="size-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linejoin="round" d="{{ $icon }}" /></svg>
                                        </span>
                                        <span class="font-extrabold">{{ __('theme::farma.benefit_'.$benefit) }}<span class="block text-sm font-semibold text-muted">{{ __('theme::farma.benefit_'.$benefit.'_text') }}</span></span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        @if (filled($themeOptions->get('about_link')))
                            <a href="{{ $themeOptions->get('about_link') }}" class="farma-link mt-7 inline-block">{{ __('theme::farma.about_more') }}</a>
                        @endif
                    </div>
                </section>
            @endif
        </div>

        {{-- Hodnotenia zákazníkov --}}
        @if ($homeReviews->isNotEmpty())
            <section class="bg-cream py-14 sm:py-20" aria-labelledby="home-reviews">
                <div class="container-shop">
                    <h2 id="home-reviews" class="farma-h2">{{ __('theme::farma.reviews_title') }}</h2>
                    <div class="mt-8 grid gap-8 md:grid-cols-3">
                        @foreach ($homeReviews as $homeReview)
                            <figure class="border-l-[3px] border-primary py-1 pl-5">
                                @include('theme::partials.rating-stars', ['rating' => $homeReview->rating, 'size' => 'text-sm'])
                                <blockquote class="mt-2.5 text-lg text-zinc-700">„{{ \Illuminate\Support\Str::limit($homeReview->body, 220) }}“</blockquote>
                                <figcaption class="mt-3 text-sm font-extrabold text-muted">{{ $homeReview->author_name }}</figcaption>
                            </figure>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        {{-- Časté otázky --}}
        @if ($faqItems->isNotEmpty())
            <section class="container-shop grid gap-6 py-14 sm:py-20 lg:grid-cols-[1fr_1.5fr] lg:gap-16" aria-labelledby="home-faq">
                <div>
                    <h2 id="home-faq" class="farma-h2">{{ __('theme::farma.faq_title') }}</h2>
                    <p class="mt-3 text-muted">{{ __('theme::farma.faq_text') }}</p>
                    @if ($shop->contact_email)
                        <a href="mailto:{{ $shop->contact_email }}" class="btn btn-primary mt-6">{{ __('theme::shop.contact') }}</a>
                    @endif
                </div>
                <div class="divide-y divide-[var(--farma-line)] border-y border-line">
                    @foreach ($faqItems as $index => $faqItem)
                        <details class="group py-5" @if ($index === 0) open @endif>
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-lg font-extrabold">
                                {{ $faqItem['q'] }}
                                <span class="text-2xl leading-none text-primary group-open:rotate-45 transition">+</span>
                            </summary>
                            <p class="mt-2 text-zinc-600">{{ $faqItem['a'] }}</p>
                        </details>
                    @endforeach
                </div>
            </section>
        @endif
    @endif
@endsection
