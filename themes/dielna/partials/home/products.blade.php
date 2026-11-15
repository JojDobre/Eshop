{{-- Novinky z dielne. --}}
@php($productCount = (int) $themeOptions->get('products_count', 8))
@php($homeProducts = ! empty($latestProducts) ? $latestProducts->take($productCount) : collect())
@if ($productCount > ($latestProducts?->count() ?? 0) && ($latestProducts?->count() ?? 0) >= 8)
    @php($homeProducts = \Modules\Catalog\Models\Product::query()->visible()->with(['prices', 'groupPrices', 'taxRate', 'variants' => fn ($variants) => $variants->where('is_active', true)->with(['prices', 'groupPrices'])])->latest()->limit($productCount)->get())
@endif
@if ($homeProducts->isNotEmpty())
    <section class="bg-[color-mix(in_srgb,var(--dielna-sand)_55%,transparent)]" aria-labelledby="home-products">
        <div class="{{ $layout === 'full' ? 'container-shop' : $wrap }} py-16 sm:py-20">
            <div @class(['mb-10 flex items-end justify-between gap-4', 'flex-row-reverse text-right' => $reverse])>
                <div>
                    <p class="dielna-kicker">{{ $opt('products_kicker') }}</p>
                    <h2 id="home-products" class="dielna-h2">{{ $opt('products_title') }}</h2>
                </div>
            </div>
            @include('theme::partials.product-grid', ['products' => $homeProducts])
        </div>
    </section>
@endif
