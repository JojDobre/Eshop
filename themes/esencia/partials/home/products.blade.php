{{-- Bestsellery: mriežka produktov s odkazmi na hlavné kategórie. --}}
@php($productCount = (int) $themeOptions->get('products_count', 8))
@php($homeProducts = ! empty($latestProducts) ? $latestProducts->take($productCount) : collect())
@if ($productCount > ($latestProducts?->count() ?? 0) && ($latestProducts?->count() ?? 0) >= 8)
    @php($homeProducts = \Modules\Catalog\Models\Product::query()->visible()->with(['prices', 'groupPrices', 'taxRate', 'variants' => fn ($variants) => $variants->where('is_active', true)->with(['prices', 'groupPrices'])])->latest()->limit($productCount)->get())
@endif
@if ($homeProducts->isNotEmpty())
    <section class="{{ $wrap }} py-10" aria-labelledby="home-products">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <h2 id="home-products" class="es-h2">{{ $opt('products_title') }}</h2>
            @if (! empty($homeCategories) && $homeCategories->count() > 1)
                <div class="flex flex-wrap gap-2">
                    @foreach ($homeCategories->take(4) as $chipCategory)
                        <a href="{{ $chipCategory->url() }}" class="es-chip">{{ $chipCategory->name }}</a>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="mt-6">@include('theme::partials.product-grid', ['products' => $homeProducts])</div>
    </section>
@endif
