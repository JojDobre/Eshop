{{-- Izbové rastliny: produkty z vybranej kategórie s fotkou v tvare lupeňa. --}}
@php
    $plantsCategory = (int) $themeOptions->get('plants_category') > 0 ? \Modules\Catalog\Models\Category::query()->find((int) $themeOptions->get('plants_category')) : null;
    $plants = $plantsCategory
        ? \Modules\Catalog\Models\Product::query()->visible()->with(['prices', 'groupPrices', 'taxRate', 'variants'])
            ->whereHas('categories', fn ($query) => $query->where('categories.id', $plantsCategory->id))->latest()->limit(4)->get()
        : collect();
@endphp
@if ($plants->isNotEmpty())
    <section class="{{ $wrap }} py-10" aria-labelledby="home-plants">
        <div class="flex items-end justify-between gap-4">
            <h2 id="home-plants" class="lp-h2">{{ $opt('plants_title') }}</h2>
            <a href="{{ $plantsCategory->url() }}" class="lp-link shrink-0">{{ __('theme::lupen.all_plants') }}</a>
        </div>
        <div class="mt-8 grid grid-cols-2 gap-4 sm:gap-5 lg:grid-cols-4">
            @foreach ($plants as $plant)
                @php
                    $plantPrice = $plant->displayPrice($plant->priceFrom());
                @endphp
                <a href="{{ $plant->url() }}" class="lp-zoom group block">
                    <span @class(['block aspect-[4/5] overflow-hidden bg-soft2', 'leaf-sm' => $loop->odd, 'leaf-sm-r' => $loop->even])>@include('theme::partials.product-image', ['url' => $plant->mainImageUrl(), 'alt' => $plant->name])</span>
                    <span class="mt-3 flex items-start justify-between gap-2">
                        <span class="min-w-0"><b class="block font-head text-lg font-normal sm:text-xl">{{ $plant->name }}</b>@if (filled($plant->short_description))<span class="line-clamp-1 text-sm text-muted">{{ strip_tags((string) $plant->short_description) }}</span>@endif</span>
                        @if ($plantPrice !== null)<b class="whitespace-nowrap">{{ format_price($plantPrice) }}</b>@endif
                    </span>
                </a>
            @endforeach
        </div>
    </section>
@endif
