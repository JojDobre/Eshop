{{-- Najobľúbenejšie kytice: mriežka produktov s veľkosťami a odkazy na kategórie. --}}
@php
    $productCount = (int) $themeOptions->get('products_count', 4);
    $homeProducts = \Modules\Catalog\Models\Product::query()->visible()
        ->with(['prices', 'groupPrices', 'taxRate', 'variants' => fn ($variants) => $variants->where('is_active', true)->with(['prices', 'groupPrices', 'values']), 'options.values'])
        ->when((int) $themeOptions->get('plants_category') > 0, fn ($query) => $query->whereDoesntHave('categories', fn ($categories) => $categories->where('categories.id', (int) $themeOptions->get('plants_category'))))
        ->latest()->orderBy('id')->limit($productCount)->get();
@endphp
@if ($homeProducts->isNotEmpty())
    <section class="{{ $wrap }} py-10" aria-labelledby="home-products">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <h2 id="home-products" class="lp-h2">{{ $opt('products_title') }}</h2>
            @if (! empty($homeCategories) && $homeCategories->count() > 1)
                <div class="flex flex-wrap gap-2">
                    @foreach ($homeCategories->take(4) as $chipCategory)
                        <a href="{{ $chipCategory->url() }}" class="lp-chip">{{ $chipCategory->name }}</a>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="mt-8">@include('theme::partials.product-grid', ['products' => $homeProducts])</div>
    </section>
@endif
