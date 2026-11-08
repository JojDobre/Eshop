@extends('theme::layouts.app')

@push('head')
    <script type="application/ld+json">{!! json_encode(array_filter([
        '@'.'context' => 'https://schema.org',
        '@graph' => [
            array_filter(['@type' => 'Organization', 'name' => $company->company_name ?: $shop->shop_name, 'url' => route('storefront.home'), 'email' => $shop->contact_email, 'telephone' => $shop->contact_phone]),
            ['@type' => 'WebSite', 'name' => $shop->shop_name, 'url' => route('storefront.home'), 'potentialAction' => Route::has('catalog.search') ? [
                '@type' => 'SearchAction', 'target' => route('catalog.search').'?q={search_term_string}', 'query-input' => 'required name=search_term_string',
            ] : null],
        ],
    ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
    @if ($homeBlocks = \App\Models\BlockLayout::blocksFor(\App\Models\BlockLayout::HOME))
        {!! render_blocks($homeBlocks) !!}
    @else
        {{-- Predvolená úvodná stránka Fresh: úvod s kategóriami vedľa seba, výhody a novinky. --}}
        <section class="container-shop grid gap-6 py-8 lg:grid-cols-3">
            <div class="flex flex-col justify-center rounded-3xl bg-accent p-8 text-white sm:p-12 lg:col-span-2">
                <h1 class="text-3xl font-bold tracking-tight sm:text-5xl">{{ $shop->shop_name }}</h1>
                @if ($shop->tagline)
                    <p class="mt-4 text-lg text-white/90">{{ $shop->tagline }}</p>
                @endif
                @if (! empty($homeCategories) && $homeCategories->isNotEmpty())
                    <a href="{{ $homeCategories->first()->url() }}" class="btn mt-8 self-start bg-white text-zinc-900 hover:bg-zinc-100">{{ __('theme::shop.shop_now') }}</a>
                @endif
            </div>
            @if (! empty($homeCategories) && $homeCategories->isNotEmpty())
                <ul class="grid grid-cols-2 gap-3 lg:grid-cols-1">
                    @foreach ($homeCategories->take(4) as $homeCategory)
                        <li>
                            <a href="{{ $homeCategory->url() }}" class="flex h-full items-center justify-between gap-3 rounded-2xl border border-zinc-200 p-4 font-semibold hover:border-accent">
                                {{ $homeCategory->name }}
                                <svg class="size-5 text-accent" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        @if ($themeOptions->get('show_benefits', true))
            <section class="container-shop grid gap-4 py-6 sm:grid-cols-3" aria-label="{{ __('theme::fresh.benefits') }}">
                @foreach (['delivery', 'fresh', 'support'] as $benefit)
                    <div class="rounded-2xl bg-zinc-50 p-5">
                        <p class="font-semibold text-accent">{{ __('theme::fresh.benefit_'.$benefit) }}</p>
                        <p class="mt-1 text-sm text-zinc-600">{{ __('theme::fresh.benefit_'.$benefit.'_text') }}</p>
                    </div>
                @endforeach
            </section>
        @endif

        @if (! empty($latestProducts) && $latestProducts->isNotEmpty())
            <section class="container-shop py-10" aria-labelledby="home-latest">
                <h2 id="home-latest" class="text-2xl font-bold tracking-tight">{{ __('theme::shop.latest_products') }}</h2>
                <div class="mt-6">
                    @include('theme::partials.product-grid', ['products' => $latestProducts])
                </div>
            </section>
        @else
            <section class="container-shop py-16 text-zinc-600">{{ __('theme::shop.coming_soon') }}</section>
        @endif
    @endif
@endsection
