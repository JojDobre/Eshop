{{-- Nové vydania: najnovšie produkty v kartách s obálkou a odkazy na hlavné kategórie. --}}
@php
    $newCount = (int) $themeOptions->get('new_count', 4);
    $excluded = array_filter([(int) $themeOptions->get('stationery_category')]);
    $newBooks = \Modules\Catalog\Models\Product::query()->visible()
        ->with(['prices', 'groupPrices', 'taxRate', 'variants' => fn ($variants) => $variants->where('is_active', true)->with(['prices', 'groupPrices', 'values']), 'options.values'])
        ->when($excluded !== [], fn ($query) => $query->whereDoesntHave('categories', fn ($categories) => $categories->whereIn('categories.id', $excluded)))
        ->latest()->orderByDesc('id')->limit($newCount)->get();
@endphp
@if ($newBooks->isNotEmpty())
    <section class="{{ $wrap }} py-16" aria-labelledby="home-new">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <h2 id="home-new" class="kp-h2">{{ $opt('new_title') }}</h2>
            @if (! empty($homeCategories) && $homeCategories->count() > 1)
                <ul class="flex flex-wrap gap-2">
                    @foreach ($homeCategories->take(4) as $chipCategory)
                        <li><a href="{{ $chipCategory->url() }}" class="kp-chip">{{ $chipCategory->name }}</a></li>
                    @endforeach
                </ul>
            @endif
        </div>
        <div class="mt-8">@include('theme::partials.product-grid', ['products' => $newBooks])</div>
    </section>
@endif
