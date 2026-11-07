@php($cardPrice = $product->priceIn())
@php($cardCompareAt = $product->compareAtPriceIn())
<article class="group relative flex flex-col">
    <div class="relative aspect-square overflow-hidden rounded-xl bg-zinc-100">
        @if ($cardCompareAt)
            <span class="absolute left-3 top-3 z-10 rounded-full bg-primary px-2 py-0.5 text-xs font-semibold text-primary-foreground">−{{ (int) round((1 - $cardPrice / $cardCompareAt) * 100) }} %</span>
        @endif
        @include('theme::partials.product-image', ['url' => $product->mainImageUrl(), 'alt' => $product->name, 'class' => 'transition duration-300 group-hover:scale-[1.03]'])
    </div>
    <div class="mt-3 flex flex-1 flex-col">
        <h3 class="text-sm font-medium text-zinc-900">
            <a href="{{ $product->url() }}" class="after:absolute after:inset-0">{{ $product->name }}</a>
        </h3>
        <div class="mt-1 flex items-baseline justify-between gap-2 text-sm">
            <span>
                <span class="font-semibold">{{ $cardPrice !== null ? format_price($cardPrice) : '' }}</span>
                @if ($cardCompareAt)
                    <del class="ml-1 text-xs text-zinc-500">{{ format_price($cardCompareAt) }}</del>
                @endif
            </span>
            @unless ($product->isAvailable())
                <span class="text-xs text-zinc-500">{{ __('theme::shop.sold_out') }}</span>
            @endunless
        </div>
    </div>
</article>
