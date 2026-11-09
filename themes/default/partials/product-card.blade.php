@php($cardPrice = $product->displayPrice($product->priceFrom()))
@php($cardFrom = $product->hasVaryingPrices())
@php($cardCompareAt = $product->displayPrice($product->compareAtPriceIn()))
<article class="group relative flex flex-col">
    <div class="relative aspect-square overflow-hidden rounded-xl bg-zinc-100">
        @if (($cardLabels = $product->displayLabels($cardPrice, $cardCompareAt)) !== [])
            <div class="absolute left-3 top-3 z-10 flex flex-col items-start gap-1">
                @foreach ($cardLabels as $cardLabel)
                    <span @class(['rounded-full px-2 py-0.5 text-xs font-semibold', 'bg-primary text-primary-foreground' => $cardLabel['style'] === null && $cardLabel['kind'] === 'sale', 'bg-white text-zinc-900 shadow-sm' => $cardLabel['style'] === null && $cardLabel['kind'] === 'new']) @if ($cardLabel['style']) style="{{ $cardLabel['style'] }}" @endif>{{ $cardLabel['text'] }}</span>
                @endforeach
            </div>
        @endif
        @include('theme::partials.product-image', ['url' => $product->mainImageUrl(), 'alt' => $product->name, 'class' => 'transition duration-300 group-hover:scale-[1.03]'])
    </div>
    <div class="mt-3 flex flex-1 flex-col">
        <h3 class="text-sm font-medium text-zinc-900">
            <a href="{{ $product->url() }}" class="after:absolute after:inset-0">{{ $product->name }}</a>
        </h3>
        @if (! empty($reviewsEnabled) && $product->reviews_count > 0)
            <p class="mt-0.5 flex items-center gap-1 text-xs text-zinc-500">@include('theme::partials.rating-stars', ['rating' => $product->rating_average, 'size' => 'text-xs']) ({{ $product->reviews_count }})</p>
        @endif
        <div class="mt-1 flex items-baseline justify-between gap-2 text-sm">
            <span>
                <span class="font-semibold">{{ $cardPrice === null ? '' : ($cardFrom ? __('theme::shop.price_from', ['price' => format_price($cardPrice)]) : format_price($cardPrice)) }}@if ($cardPrice !== null && $product::showsNetPrices()) <span class="text-xs font-normal text-zinc-500">{{ __('theme::shop.excl_vat') }}</span>@endif</span>
                @if ($cardCompareAt)
                    <del class="ml-1 text-xs text-zinc-500">{{ format_price($cardCompareAt) }}</del>
                @endif
                @if ($cardUnitPrice = $product->unitPriceIn())
                    <span class="block text-xs text-zinc-500">{{ __('theme::shop.unit_price', ['price' => format_price($product->displayPrice($cardUnitPrice)), 'unit' => __('theme::shop.units.'.$product->unit())]) }}</span>
                @endif
            </span>
            @if (! $product->isAvailable())
                <span class="text-xs text-zinc-500">{{ __('theme::shop.sold_out') }}</span>
            @elseif ($product->isPreorder())
                <span class="text-xs font-medium text-amber-700">{{ __('theme::shop.preorder') }}</span>
            @endif
        </div>
    </div>
</article>
