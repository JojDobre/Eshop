@extends('theme::layouts.app')

@php
    $price = $product->priceIn();
    $images = $product->imageUrls();
    $available = $price !== null && $product->isAvailable();
    $maxQuantity = $product->maxPurchasableQuantity();
@endphp

@section('title', $product->meta_title ?: $product->name)
@section('meta_description', $product->meta_description ?: ($product->short_description ?: \Illuminate\Support\Str::limit(strip_tags((string) $product->description), 160)))
@section('canonical', $product->url())
@if ($images)
    @section('og_image', $images[0])
@endif

@push('head')
    <script type="application/ld+json">{!! json_encode(array_filter([
        // Kľúč je zložený z dvoch častí, inak by ho Blade spracoval ako svoju direktívu.
        '@'.'context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->name,
        'description' => $product->short_description ?: strip_tags((string) $product->description),
        'sku' => $product->sku,
        'image' => $images ?: null,
        'offers' => $price === null ? null : [
            '@type' => 'Offer',
            'price' => \App\Core\Support\Price::toDecimal($price, app(\App\Core\Support\CurrencyContext::class)->current()),
            'priceCurrency' => app(\App\Core\Support\CurrencyContext::class)->current(),
            'availability' => $available ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'url' => $product->url(),
        ],
    ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
    <div class="container-shop py-8">
        @include('theme::partials.breadcrumbs', ['items' => [
            ...($category ? [...array_map(fn ($ancestor) => [$ancestor->name, $ancestor->url()], $category->ancestors()), [$category->name, $category->url()]] : []),
            [$product->name, null],
        ]])

        <div class="mt-8 grid gap-10 lg:grid-cols-2 lg:gap-16">
            <div x-data="{ active: 0 }">
                <div class="aspect-square overflow-hidden rounded-2xl bg-zinc-100">
                    @if ($images)
                        @foreach ($images as $index => $image)
                            <img src="{{ $image }}" alt="{{ $product->name }}" x-show="active === {{ $index }}" @if ($index > 0) x-cloak loading="lazy" @endif class="h-full w-full object-cover">
                        @endforeach
                    @else
                        @include('theme::partials.product-image', ['url' => null, 'alt' => $product->name])
                    @endif
                </div>
                @if (count($images) > 1)
                    <div class="mt-4 grid grid-cols-5 gap-3">
                        @foreach ($images as $index => $image)
                            <button type="button" @click="active = {{ $index }}" class="aspect-square overflow-hidden rounded-lg bg-zinc-100 ring-offset-2"
                                    :class="active === {{ $index }} ? 'ring-2 ring-primary' : 'opacity-70 hover:opacity-100'"
                                    aria-label="{{ __('theme::shop.show_image', ['number' => $index + 1]) }}">
                                <img src="{{ $image }}" alt="" loading="lazy" class="h-full w-full object-cover">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div>
                <h1 class="text-3xl font-semibold tracking-tight">{{ $product->name }}</h1>
                @if ($product->sku)
                    <p class="mt-2 text-sm text-zinc-500">{{ __('theme::shop.sku') }}: {{ $product->sku }}</p>
                @endif

                <p class="mt-6 text-3xl font-semibold">{{ $price !== null ? format_price($price) : __('theme::shop.price_unavailable') }}</p>
                <p class="mt-1 text-sm text-zinc-500">{{ __('theme::shop.price_includes_vat') }}</p>

                <p @class(['mt-4 inline-flex items-center gap-2 text-sm font-medium', 'text-emerald-700' => $available, 'text-zinc-500' => ! $available])>
                    <span @class(['size-2 rounded-full', 'bg-emerald-500' => $available, 'bg-zinc-400' => ! $available]) aria-hidden="true"></span>
                    {{ $available ? ($maxQuantity !== null && $maxQuantity <= 5 ? __('theme::shop.in_stock_few', ['count' => $maxQuantity]) : __('theme::shop.in_stock')) : __('theme::shop.sold_out') }}
                </p>

                @if ($product->short_description)
                    <p class="mt-6 text-zinc-600">{{ $product->short_description }}</p>
                @endif

                @if ($available && Route::has('cart.add'))
                    <form method="POST" action="{{ route('cart.add') }}" class="mt-8 flex gap-3">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <label for="quantity" class="sr-only">{{ __('theme::shop.quantity') }}</label>
                        <input id="quantity" type="number" name="quantity" value="1" min="1" @if ($maxQuantity !== null) max="{{ $maxQuantity }}" @endif class="input w-20 text-center">
                        <button type="submit" class="btn btn-primary flex-1 sm:flex-none sm:px-10">{{ __('theme::shop.add_to_cart') }}</button>
                    </form>
                @endif

                @if ($product->description)
                    <div class="prose prose-zinc mt-10 max-w-none border-t border-zinc-100 pt-8">{!! $product->description !!}</div>
                @endif
            </div>
        </div>

        @if ($relatedProducts->isNotEmpty())
            <section class="mt-20" aria-labelledby="related">
                <h2 id="related" class="text-xl font-semibold tracking-tight">{{ __('theme::shop.related_products') }}</h2>
                <div class="mt-6">@include('theme::partials.product-grid', ['products' => $relatedProducts])</div>
            </section>
        @endif
    </div>
@endsection
