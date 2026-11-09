{{-- Najnovšie produkty. --}}
@if (! empty($latestProducts) && $latestProducts->isNotEmpty())
    @php($productsLink = $themeOptions->get('products_link') ?: (! empty($homeCategories) && $homeCategories->isNotEmpty() ? $homeCategories->first()->url() : null))
    <section class="{{ $layout === 'full' ? 'container-shop' : $wrap }} farma-section py-14 sm:py-20" aria-labelledby="home-products">
        <div @class(['mb-6 flex items-end justify-between gap-4 sm:mb-8', 'flex-row-reverse' => $reverse])>
            <h2 id="home-products" class="farma-h2">{{ $themeOptions->get('products_title') ?: __('theme::shop.latest_products') }}</h2>
            @if ($productsLink)
                <a href="{{ $productsLink }}" class="farma-link text-sm sm:text-base">{{ __('theme::farma.show_all') }}</a>
            @endif
        </div>
        @include('theme::partials.product-grid', ['products' => $latestProducts])
    </section>
@endif
