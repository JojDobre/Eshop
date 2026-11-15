{{-- Karta produktu Dielne: fotka na výšku so štítkom, rýchle pridanie do košíka, názov pätkovým písmom, krátky popis a cena. --}}
@php
    $cardPrice = $product->displayPrice($product->priceFrom());
    $cardFrom = $product->hasVaryingPrices();
    $cardCompareAt = $product->displayPrice($product->compareAtPriceIn());
    $cardAvailable = $product->isAvailable();
    $cardStatus = $product->displayAvailability();
    $cardLabels = $product->displayLabels($cardPrice, $cardCompareAt);
    $cardQuickAdd = $cardAvailable && ! $product->hasVariants() && Route::has('cart.add');
    $cardNote = trim(strip_tags((string) $product->short_description));
@endphp
<article @class(['group relative flex flex-col', 'opacity-90' => ! $cardAvailable])>
    <div class="dielna-zoom relative aspect-[4/5] overflow-hidden rounded-2xl bg-white">
        @if ($cardLabels !== [])
            <div class="absolute left-3 top-3 z-10 flex flex-col items-start gap-1">
                @foreach ($cardLabels as $cardLabel)
                    <span @class(['rounded-full px-3 py-1 text-xs font-semibold', 'bg-primary text-primary-foreground' => $cardLabel['style'] === null && $cardLabel['kind'] === 'sale', 'bg-sage text-white' => $cardLabel['style'] === null && $cardLabel['kind'] === 'new', 'bg-cream text-accent' => $cardLabel['style'] === null && ! in_array($cardLabel['kind'], ['sale', 'new'], true)]) @if ($cardLabel['style']) style="{{ $cardLabel['style'] }}" @endif>{{ $cardLabel['text'] }}</span>
                @endforeach
            </div>
        @endif
        @include('theme::partials.product-image', ['url' => $product->mainImageUrl(), 'alt' => $product->name, 'class' => $cardAvailable ? '' : 'opacity-60'])

        @if ($cardQuickAdd)
            <form method="POST" action="{{ route('cart.add') }}" class="absolute bottom-3 right-3 z-20 transition sm:translate-y-2 sm:opacity-0 sm:group-hover:translate-y-0 sm:group-hover:opacity-100 sm:group-focus-within:opacity-100" data-ajax-cart>
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <button type="submit" class="grid size-11 place-items-center rounded-full bg-ink text-cream shadow-lg transition hover:scale-105" aria-label="{{ __('theme::dielna.add_to_cart', ['name' => $product->name]) }}">
                    <svg class="cart-icon-default size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14" /></svg>
                    <svg class="cart-icon-done size-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" /></svg>
                </button>
            </form>
        @endif
    </div>

    <h3 class="mt-4 font-serif text-lg leading-snug">
        <a href="{{ $product->url() }}" class="after:absolute after:inset-0">{{ $product->name }}</a>
    </h3>
    @if ($cardNote !== '')
        <p class="line-clamp-1 text-sm text-muted">{{ $cardNote }}</p>
    @endif
    @if (! empty($reviewsEnabled) && $product->reviews_count > 0)
        <p class="mt-0.5 flex items-center gap-1 text-xs text-muted">@include('theme::partials.rating-stars', ['rating' => $product->rating_average, 'size' => 'text-xs']) ({{ $product->reviews_count }})</p>
    @endif
    <p class="mt-1.5">
        @if ($cardCompareAt)<del class="mr-1.5 text-sm text-muted">{{ format_price($cardCompareAt) }}</del>@endif
        <span class="font-semibold">{{ $cardPrice === null ? '' : ($cardFrom ? __('theme::shop.price_from', ['price' => format_price($cardPrice)]) : format_price($cardPrice)) }}</span>
        @if ($cardPrice !== null && $product::showsNetPrices())<span class="text-xs text-muted">{{ __('theme::shop.excl_vat') }}</span>@endif
        @if ($cardUnitPrice = $product->unitPriceIn())
            <span class="block text-xs text-muted">{{ __('theme::shop.unit_price', ['price' => format_price($product->displayPrice($cardUnitPrice)), 'unit' => __('theme::shop.units.'.$product->unit())]) }}</span>
        @endif
    </p>
    @if ($cardStatus && ! $cardAvailable)
        <p class="mt-1 flex items-center gap-1.5 text-xs text-muted"><span class="size-1.5 rounded-full" style="background: {{ $cardStatus['color'] }}"></span>{{ $cardStatus['text'] }}</p>
    @endif
</article>
