{{--
    Karta produktu Lupeňa: fotka v tvare lupeňa s malým štítkom, názov a veľkosti s cenou priamo na karte
    (kliknutím na veľkosť sa kytica pridá do košíka). Produkty bez variantov majú tlačidlo Do košíka.
--}}
@php
    $cardPrice = $product->displayPrice($product->priceFrom());
    $cardFrom = $product->hasVaryingPrices();
    $cardCompareAt = $product->displayPrice($product->compareAtPriceIn());
    $cardAvailable = $product->isAvailable();
    $cardStatus = $product->displayAvailability();
    $cardLabel = collect($product->displayLabels($cardPrice, $cardCompareAt))->first();
    $cardSizes = collect();

    // Veľkosti: varianty s jednou voľbou (napr. Malá / Stredná / Veľká), najviac tri.
    if ($product->hasVariants()) {
        $product->loadMissing(['options.values', 'variants.values']);

        if ($product->options->count() === 1) {
            $cardSizes = $product->variants->filter(fn ($variant) => $variant->is_active)
                ->sortBy(fn ($variant) => $variant->values->first()?->position ?? 0)
                ->map(fn ($variant) => [
                    'variant' => $variant->setRelation('product', $product),
                    'label' => $variant->values->first()?->value ?? $variant->name(),
                    'price' => $product->displayPrice($variant->priceIn()),
                ])->take(3)->values();
        }
    }
@endphp
<article @class(['group relative flex flex-col', 'opacity-80' => ! $cardAvailable])>
    <div class="lp-zoom leaf-sm relative aspect-[4/5] overflow-hidden bg-soft">
        @include('theme::partials.product-image', ['url' => $product->mainImageUrl(), 'alt' => $product->name, 'class' => $cardAvailable ? '' : 'opacity-60'])
        @if ($cardLabel)
            <span @class(['absolute left-3 top-3 z-10 max-w-[calc(100%-1.5rem)] truncate rounded-full px-2.5 py-1 text-[11px] font-bold sm:left-4 sm:top-4 sm:text-xs', 'bg-ac text-onac' => $cardLabel['style'] === null && $cardLabel['kind'] === 'sale', 'bg-ac2 text-onac2' => $cardLabel['style'] === null && $cardLabel['kind'] !== 'sale']) @if ($cardLabel['style']) style="{{ $cardLabel['style'] }}" @endif>{{ $cardLabel['text'] }}</span>
        @endif
    </div>

    <h3 class="mt-4 font-head text-lg leading-snug sm:text-xl">
        <a href="{{ $product->url() }}" class="after:absolute after:inset-0 after:z-0">{{ $product->name }}</a>
    </h3>

    @if ($cardSizes->isNotEmpty())
        <div class="relative z-10 mt-3 grid gap-1.5 sm:[grid-template-columns:repeat(var(--sizes),minmax(0,1fr))]" style="--sizes: {{ $cardSizes->count() }}" title="{{ __('theme::lupen.sizes_hint') }}">
            @foreach ($cardSizes as $cardSize)
                @if ($cardSize['variant']->isAvailable() && Route::has('cart.add'))
                    <form method="POST" action="{{ route('cart.add') }}" data-ajax-cart>
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <input type="hidden" name="variant_id" value="{{ $cardSize['variant']->id }}">
                        <button type="submit" class="w-full rounded-xl border border-line px-1 py-1.5 text-center transition hover:border-[var(--lp-ac)] hover:bg-ac hover:text-onac" aria-label="{{ __('theme::lupen.add_size', ['size' => $cardSize['label']]) }}: {{ $product->name }}">
                            <span class="cart-icon-default flex items-center justify-between gap-2 px-2 sm:block sm:px-0">
                                <span class="block truncate text-xs font-semibold sm:text-[11px]">{{ $cardSize['label'] }}</span>
                                <span class="block whitespace-nowrap text-sm font-bold">{{ $cardSize['price'] !== null ? format_price($cardSize['price']) : '' }}</span>
                            </span>
                            <span class="cart-icon-done py-2 text-sm font-bold">✓</span>
                        </button>
                    </form>
                @else
                    <span class="flex items-center justify-between gap-2 rounded-xl border border-dashed border-line px-3 py-1.5 text-center text-muted sm:block sm:px-1">
                        <span class="block truncate text-xs font-semibold line-through sm:text-[11px]">{{ $cardSize['label'] }}</span>
                        <span class="block whitespace-nowrap text-sm">{{ $cardSize['price'] !== null ? format_price($cardSize['price']) : '' }}</span>
                    </span>
                @endif
            @endforeach
        </div>
    @else
        <p class="mt-1">
            @if ($cardCompareAt)<del class="mr-1.5 text-sm text-muted">{{ format_price($cardCompareAt) }}</del>@endif
            <span class="font-bold">{{ $cardPrice === null ? '' : ($cardFrom ? __('theme::shop.price_from', ['price' => format_price($cardPrice)]) : format_price($cardPrice)) }}</span>
        </p>
        @if ($cardAvailable && ! $product->hasVariants() && Route::has('cart.add'))
            <form method="POST" action="{{ route('cart.add') }}" class="relative z-10 mt-3" data-ajax-cart>
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <button type="submit" class="btn btn-ac w-full !py-2.5 text-sm" aria-label="{{ __('theme::lupen.add_to_cart_label', ['name' => $product->name]) }}">
                    <span class="cart-icon-default">{{ __('theme::lupen.add_to_cart') }}</span>
                    <span class="cart-icon-done">✓</span>
                </button>
            </form>
        @elseif ($cardAvailable)
            <a href="{{ $product->url() }}" class="btn btn-secondary relative z-10 mt-3 w-full !py-2.5 text-sm">{{ __('theme::lupen.choose') }}</a>
        @endif
    @endif
    @if ($cardStatus && ! $cardAvailable)
        <p class="mt-2 flex items-center gap-1.5 text-xs text-muted"><span class="size-1.5 rounded-full" style="background: {{ $cardStatus['color'] }}"></span>{{ $cardStatus['text'] }}</p>
    @endif
</article>
