{{-- Produkty z dvora: najnovšie produkty (alebo z vybranej kategórie) a odkazy na hlavné kategórie. --}}
@php
    $productCount = (int) $themeOptions->get('products_count', 4);
    $productsCategory = (int) $themeOptions->get('products_category') > 0 ? \Modules\Catalog\Models\Category::query()->find((int) $themeOptions->get('products_category')) : null;
    $excluded = array_filter([(int) $themeOptions->get('animals_category')]);
    $farmProducts = \Modules\Catalog\Models\Product::query()->visible()
        ->with(['prices', 'groupPrices', 'taxRate', 'productType', 'variants' => fn ($variants) => $variants->where('is_active', true)->with(['prices', 'groupPrices', 'values']), 'options.values'])
        ->when($productsCategory, fn ($query) => $query->whereHas('categories', fn ($categories) => $categories->where('categories.id', $productsCategory->id)))
        ->when(! $productsCategory && $excluded !== [], fn ($query) => $query->whereDoesntHave('categories', fn ($categories) => $categories->whereIn('categories.id', $excluded)))
        ->latest()->orderByDesc('id')->limit($productCount)->get();
@endphp
@if ($farmProducts->isNotEmpty())
    <section class="{{ $wrap }} pb-16" aria-labelledby="home-products">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="dv-kicker">{{ $opt('products_kicker') }}</p>
                <h2 id="home-products" class="dv-h2 mt-2">{{ $opt('products_title') }}</h2>
            </div>
            @if (! empty($homeCategories) && $homeCategories->count() > 1)
                <ul class="flex flex-wrap gap-2">
                    @foreach ($homeCategories->reject(fn ($category) => in_array($category->id, $excluded, true))->take(4) as $chipCategory)
                        <li><a href="{{ $chipCategory->url() }}" class="dv-chip">{{ $chipCategory->name }}</a></li>
                    @endforeach
                </ul>
            @endif
        </div>
        <div class="mt-8">@include('theme::partials.product-grid', ['products' => $farmProducts])</div>
    </section>
@endif
