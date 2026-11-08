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
    {{-- Úvodná stránka zložená z blokov (Obsah → Úvodná stránka), inak predvolený obsah šablóny. --}}
    @if ($homeBlocks = \App\Models\BlockLayout::blocksFor(\App\Models\BlockLayout::HOME))
        {!! render_blocks($homeBlocks) !!}
    @else
    <section class="border-b border-zinc-100 bg-gradient-to-b from-zinc-50 to-white">
        <div class="container-shop py-20 sm:py-28">
            <div class="max-w-2xl">
                <h1 class="text-4xl font-semibold tracking-tight sm:text-5xl">{{ $shop->shop_name }}</h1>
                @if ($shop->tagline)
                    <p class="mt-5 text-lg text-zinc-600">{{ $shop->tagline }}</p>
                @endif
                @if (! empty($homeCategories) && $homeCategories->isNotEmpty())
                    <a href="{{ $homeCategories->first()->url() }}" class="btn btn-primary mt-8">{{ __('theme::shop.shop_now') }}</a>
                @endif
            </div>
        </div>
    </section>

    @if (! empty($homeCategories) && $homeCategories->isNotEmpty())
        <section class="container-shop py-14" aria-labelledby="home-categories">
            <h2 id="home-categories" class="text-xl font-semibold tracking-tight">{{ __('theme::shop.categories') }}</h2>
            <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3">
                @foreach ($homeCategories as $homeCategory)
                    <a href="{{ $homeCategory->url() }}" class="group relative flex aspect-[4/3] items-end overflow-hidden rounded-xl bg-zinc-100 p-5">
                        @if ($image = $homeCategory->imageUrl())
                            <img src="{{ $image }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]">
                            <span class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></span>
                        @endif
                        <span @class(['relative text-lg font-semibold', 'text-white' => $homeCategory->image, 'text-zinc-900' => ! $homeCategory->image])>{{ $homeCategory->name }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if (! empty($latestProducts) && $latestProducts->isNotEmpty())
        <section class="container-shop py-14" aria-labelledby="home-latest">
            <h2 id="home-latest" class="text-xl font-semibold tracking-tight">{{ __('theme::shop.latest_products') }}</h2>
            <div class="mt-6">
                @include('theme::partials.product-grid', ['products' => $latestProducts])
            </div>
        </section>
    @endif

    @if ((empty($homeCategories) || $homeCategories->isEmpty()) && (empty($latestProducts) || $latestProducts->isEmpty()))
        <section class="container-shop py-16">
            <div class="inline-flex items-center gap-3 rounded-full border border-zinc-200 px-4 py-2 text-sm text-zinc-600">
                <span class="size-2 shrink-0 rounded-full bg-primary" aria-hidden="true"></span>
                {{ __('theme::shop.coming_soon') }}
            </div>
        </section>
    @endif
    @endif
@endsection
