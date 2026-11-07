@php($cardPrice = $product->priceIn())
<article class="group relative flex flex-col">
    <div class="aspect-square overflow-hidden rounded-xl bg-zinc-100">
        @include('theme::partials.product-image', ['url' => $product->mainImageUrl(), 'alt' => $product->name, 'class' => 'transition duration-300 group-hover:scale-[1.03]'])
    </div>
    <div class="mt-3 flex flex-1 flex-col">
        <h3 class="text-sm font-medium text-zinc-900">
            <a href="{{ $product->url() }}" class="after:absolute after:inset-0">{{ $product->name }}</a>
        </h3>
        <div class="mt-1 flex items-baseline justify-between gap-2 text-sm">
            <span class="font-semibold">{{ $cardPrice !== null ? format_price($cardPrice) : '' }}</span>
            @unless ($product->isAvailable())
                <span class="text-xs text-zinc-500">{{ __('theme::shop.sold_out') }}</span>
            @endunless
        </div>
    </div>
</article>
