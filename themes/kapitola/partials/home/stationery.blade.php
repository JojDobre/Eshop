{{-- Papiernictvo: štvorcové fotky produktov z vybranej kategórie. --}}
@php
    $stationeryCategory = (int) $themeOptions->get('stationery_category') > 0 ? \Modules\Catalog\Models\Category::query()->find((int) $themeOptions->get('stationery_category')) : null;
    $stationery = $stationeryCategory
        ? \Modules\Catalog\Models\Product::query()->visible()->with(['prices', 'groupPrices', 'taxRate', 'variants'])
            ->whereHas('categories', fn ($query) => $query->where('categories.id', $stationeryCategory->id))->latest()->orderBy('id')->limit(4)->get()
        : collect();
@endphp
@if ($stationery->isNotEmpty())
    <section class="{{ $wrap }} pb-16" aria-labelledby="home-stationery">
        <div class="flex items-end justify-between gap-4">
            <h2 id="home-stationery" class="kp-h2">{{ $opt('stationery_title') }}</h2>
            <a href="{{ $stationeryCategory->url() }}" class="kp-link shrink-0 text-sm sm:text-base">{{ __('theme::kapitola.stationery_all') }}</a>
        </div>
        <div class="mt-8 grid grid-cols-2 gap-4 sm:gap-5 lg:grid-cols-4">
            @foreach ($stationery as $item)
                @php($itemPrice = $item->displayPrice($item->priceFrom()))
                <a href="{{ $item->url() }}" class="kp-zoom group block min-w-0">
                    <span class="block aspect-square overflow-hidden rounded-2xl bg-soft">@include('theme::partials.product-image', ['url' => $item->mainImageUrl(), 'alt' => $item->name])</span>
                    <span class="mt-3 flex items-start justify-between gap-2">
                        <span class="min-w-0"><b class="kp-heading block text-lg leading-snug">{{ $item->name }}</b>@if (filled($item->short_description))<span class="line-clamp-1 text-sm text-muted">{{ strip_tags((string) $item->short_description) }}</span>@endif</span>
                        @if ($itemPrice !== null)<b class="whitespace-nowrap">{{ format_price($itemPrice) }}</b>@endif
                    </span>
                </a>
            @endforeach
        </div>
    </section>
@endif
