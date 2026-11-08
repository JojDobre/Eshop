{{-- Karta produktu Fresh: rámček, výrazná cena a štítok zľavy. --}}
@php($cardPrice = $product->displayPrice($product->priceFrom()))
@php($cardFrom = $product->hasVaryingPrices())
@php($cardCompareAt = $product->displayPrice($product->compareAtPriceIn()))
<article class="group relative flex flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white transition hover:shadow-md">
    <div class="relative aspect-square overflow-hidden bg-zinc-50">
        @if ($cardCompareAt)
            <span class="absolute left-3 top-3 z-10 rounded-md bg-red-600 px-2 py-0.5 text-xs font-bold text-white">−{{ (int) round((1 - $cardPrice / $cardCompareAt) * 100) }} %</span>
        @endif
        @include('theme::partials.product-image', ['url' => $product->mainImageUrl(), 'alt' => $product->name, 'class' => 'transition duration-300 group-hover:scale-[1.03]'])
    </div>
    <div class="flex flex-1 flex-col p-4">
        <h3 class="text-sm font-medium text-zinc-900">
            <a href="{{ $product->url() }}" class="after:absolute after:inset-0">{{ $product->name }}</a>
        </h3>
        @if ($cardUnitPrice = $product->unitPriceIn())
            <p class="mt-1 text-xs text-zinc-500">{{ __('theme::shop.unit_price', ['price' => format_price($product->displayPrice($cardUnitPrice)), 'unit' => __('theme::shop.units.'.$product->unit())]) }}</p>
        @endif
        <div class="mt-auto flex items-end justify-between gap-2 pt-3">
            <span>
                @if ($cardCompareAt)
                    <del class="block text-xs text-zinc-500">{{ format_price($cardCompareAt) }}</del>
                @endif
                <span @class(['text-lg font-bold', 'text-red-600' => $cardCompareAt, 'text-zinc-900' => ! $cardCompareAt])>{{ $cardPrice === null ? '' : ($cardFrom ? __('theme::shop.price_from', ['price' => format_price($cardPrice)]) : format_price($cardPrice)) }}@if ($cardPrice !== null && $product::showsNetPrices()) <span class="text-xs font-normal text-zinc-500">{{ __('theme::shop.excl_vat') }}</span>@endif</span>
            </span>
            @if (! $product->isAvailable())
                <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs text-zinc-500">{{ __('theme::shop.sold_out') }}</span>
            @elseif ($product->isPreorder())
                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">{{ __('theme::shop.preorder') }}</span>
            @else
                <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">{{ __('theme::shop.in_stock') }}</span>
            @endif
        </div>
    </div>
</article>
