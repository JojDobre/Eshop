{{-- Karta produktu Farmy: fotka so štítkom a tlačidlom „+“, názov, cena za kus a stav skladu. --}}
@php
    $cardPrice = $product->displayPrice($product->priceFrom());
    $cardFrom = $product->hasVaryingPrices();
    $cardCompareAt = $product->displayPrice($product->compareAtPriceIn());
    $cardAvailable = $product->isAvailable();
    $cardPreorder = $cardAvailable && $product->isPreorder();
    $cardLow = $cardAvailable && ! $cardPreorder && $product->track_stock && $product->stock_quantity > 0 && $product->stock_quantity <= 3;
    $cardNew = $product->created_at?->gt(now()->subDays(30));
    $cardQuickAdd = $cardAvailable && ! $product->hasVariants() && Route::has('cart.add');
@endphp
<article @class(['group relative flex flex-col', 'opacity-90' => ! $cardAvailable])>
    <div class="relative aspect-square overflow-hidden rounded-[20px] bg-cream">
        @if ($cardCompareAt)
            <span class="absolute left-3 top-3 z-10 rounded-full bg-primary px-2.5 py-1 text-xs font-extrabold text-primary-foreground">−{{ (int) round((1 - $cardPrice / $cardCompareAt) * 100) }} %</span>
        @elseif ($cardNew)
            <span class="absolute left-3 top-3 z-10 rounded-full bg-white px-2.5 py-1 text-xs font-extrabold">{{ __('theme::farma.new') }}</span>
        @endif
        @include('theme::partials.product-image', ['url' => $product->mainImageUrl(), 'alt' => $product->name, 'class' => 'transition duration-300 group-hover:scale-[1.04]'.($cardAvailable ? '' : ' opacity-60')])

        @if ($cardQuickAdd)
            <form method="POST" action="{{ route('cart.add') }}" class="absolute bottom-3 right-3 z-20">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <button type="submit" class="grid size-11 place-items-center rounded-full bg-primary text-primary-foreground shadow-lg shadow-primary/30 transition hover:scale-105" aria-label="{{ __('theme::farma.add_to_cart', ['name' => $product->name]) }}">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14" /></svg>
                </button>
            </form>
        @else
            <span class="absolute bottom-3 right-3 z-10 grid size-11 place-items-center rounded-full bg-white text-zinc-500" aria-hidden="true">
                @if ($cardAvailable)
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6" /></svg>
                @else
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linejoin="round" d="M6 16V11a6 6 0 0 1 12 0v5l2 2H4z" /><path stroke-linecap="round" d="M10 21h4" /></svg>
                @endif
            </span>
        @endif
    </div>

    <h3 class="mt-3.5 font-extrabold leading-snug">
        <a href="{{ $product->url() }}" class="after:absolute after:inset-0">{{ $product->name }}</a>
    </h3>
    @if (! empty($reviewsEnabled) && $product->reviews_count > 0)
        <p class="mt-0.5 flex items-center gap-1 text-xs text-muted">@include('theme::partials.rating-stars', ['rating' => $product->rating_average, 'size' => 'text-xs']) ({{ $product->reviews_count }})</p>
    @endif
    <div class="mt-1 flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
        <p>
            <span class="text-lg font-black">{{ $cardPrice === null ? '' : ($cardFrom ? __('theme::shop.price_from', ['price' => format_price($cardPrice)]) : format_price($cardPrice)) }}</span>
            @if ($cardPrice !== null && $product::showsNetPrices())<span class="text-xs font-bold text-muted">{{ __('theme::shop.excl_vat') }}</span>@endif
            @if ($cardCompareAt)<del class="ml-1 text-sm font-bold text-zinc-400">{{ format_price($cardCompareAt) }}</del>@endif
            @if ($cardUnitPrice = $product->unitPriceIn())
                <span class="block text-xs font-semibold text-muted">{{ __('theme::shop.unit_price', ['price' => format_price($product->displayPrice($cardUnitPrice)), 'unit' => __('theme::shop.units.'.$product->unit())]) }}</span>
            @endif
        </p>
        <p class="flex items-center gap-1.5 text-xs font-bold text-muted">
            <span @class(['size-1.5 rounded-full', 'bg-emerald-500' => $cardAvailable && ! $cardLow && ! $cardPreorder, 'bg-amber-400' => $cardLow || $cardPreorder, 'bg-zinc-300' => ! $cardAvailable])></span>
            {{ ! $cardAvailable ? __('theme::shop.sold_out') : ($cardPreorder ? __('theme::shop.preorder') : ($cardLow ? __('theme::farma.last_pieces') : __('theme::shop.in_stock'))) }}
        </p>
    </div>
</article>
