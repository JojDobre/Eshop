{{-- Novinky: najnovšie produkty, voliteľne so záložkami podľa hlavných kategórií (prepínanie bez obnovenia stránky). --}}
@php
    $newCount = (int) $themeOptions->get('new_count', 4);
    $with = ['prices', 'groupPrices', 'taxRate', 'variants' => fn ($variants) => $variants->where('is_active', true)->with(['prices', 'groupPrices', 'values']), 'options.values'];
    $tabs = collect();

    if ($themeOptions->get('new_tabs', true) && ! empty($homeCategories) && $homeCategories->count() > 1) {
        $tabs = $homeCategories->take(4)->map(fn ($category) => [
            'category' => $category,
            'products' => \Modules\Catalog\Models\Product::query()->visible()->with($with)
                ->whereHas('categories', fn ($query) => $query->whereIn('categories.id', [$category->id, ...$category->children()->pluck('id')->all()]))
                ->latest()->limit($newCount)->get(),
        ])->filter(fn ($tab) => $tab['products']->isNotEmpty())->values();
    }

    $allNew = $tabs->isEmpty() ? \Modules\Catalog\Models\Product::query()->visible()->with($with)->latest()->limit($newCount)->get() : collect();
@endphp
@if ($tabs->isNotEmpty() || $allNew->isNotEmpty())
    <section class="{{ $wrap }} py-16" aria-labelledby="home-new" x-data="{ tab: 0 }">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <h2 id="home-new" class="bt-h2">{{ $opt('new_title') }}</h2>
            @if (Route::has('catalog.search'))
                <a href="{{ route('catalog.search') }}" class="bt-link">{{ __('theme::butik.new_all') }}</a>
            @endif
        </div>
        @if ($tabs->count() > 1)
            <div class="mt-4 flex flex-wrap gap-6" role="tablist">
                @foreach ($tabs as $index => $tab)
                    <button type="button" role="tab" class="bt-tab" :aria-selected="(tab === {{ $index }}).toString()" @click="tab = {{ $index }}">{{ $tab['category']->name }}</button>
                @endforeach
            </div>
        @endif
        <div class="mt-8">
            @if ($tabs->isNotEmpty())
                @foreach ($tabs as $index => $tab)
                    <div role="tabpanel" x-show="tab === {{ $index }}" @if ($index > 0) x-cloak @endif>
                        @include('theme::partials.product-grid', ['products' => $tab['products']])
                    </div>
                @endforeach
            @else
                @include('theme::partials.product-grid', ['products' => $allNew])
            @endif
        </div>
    </section>
@endif
