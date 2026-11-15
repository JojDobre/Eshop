{{--
    Karta produktu Esencie: fotka na farebnom podklade so štítkami, hodnotenie, názov, výrobca, objemy (varianty),
    cena s cenou za mernú jednotku a tlačidlo do košíka (pri variantoch odkaz na výber).
--}}
@php
    $cardPrice = $product->displayPrice($product->priceFrom());
    $cardFrom = $product->hasVaryingPrices();
    $cardCompareAt = $product->displayPrice($product->compareAtPriceIn());
    $cardAvailable = $product->isAvailable();
    $cardStatus = $product->displayAvailability();
    // Rovnaký štítok len raz (napr. vlastný „Novinka“ aj automatický), najviac dva.
    $cardLabels = collect($product->displayLabels($cardPrice, $cardCompareAt))->unique(fn ($label) => mb_strtolower($label['text']))->take(2)->values()->all();
    $cardHasVariants = $product->hasVariants();
    $cardQuickAdd = $cardAvailable && ! $cardHasVariants && Route::has('cart.add');
    $cardSizes = $cardHasVariants ? $product->loadMissing('options.values')->options->first()?->values->take(3) : null;
    $cardMaker = $product->manufacturer_id ? $product->manufacturer?->name : null;
@endphp
<article @class(['group relative flex flex-col', 'opacity-90' => ! $cardAvailable])>
    <div class="es-zoom relative aspect-[4/5] overflow-hidden rounded-[1.5rem] bg-{{ $tint ?? 't1' }}">
        @if ($cardLabels !== [])
            <div class="absolute left-3 top-3 z-10 flex flex-col items-start gap-1">
                @foreach ($cardLabels as $cardLabel)
                    <span @class(['rounded-full px-3 py-1 text-xs font-bold', 'bg-ac text-onac' => $cardLabel['style'] === null && $cardLabel['kind'] === 'sale', 'es-sticker' => $cardLabel['style'] === null && $cardLabel['kind'] !== 'sale']) @if ($cardLabel['style']) style="{{ $cardLabel['style'] }}" @endif>{{ $cardLabel['text'] }}</span>
                @endforeach
            </div>
        @endif
        @include('theme::partials.product-image', ['url' => $product->mainImageUrl(), 'alt' => $product->name, 'class' => 'mix-blend-multiply'.($cardAvailable ? '' : ' opacity-60')])
    </div>

    @if (! empty($reviewsEnabled) && $product->reviews_count > 0)
        <p class="mt-3 flex items-center gap-1.5 text-xs">@include('theme::partials.rating-stars', ['rating' => $product->rating_average, 'size' => 'text-xs']) <span class="font-semibold">{{ number_format((float) $product->rating_average, 1, ',', ' ') }}</span><span class="text-muted">({{ $product->reviews_count }})</span></p>
    @endif
    <h3 @class(['text-[15px] font-semibold leading-snug', 'mt-3' => empty($reviewsEnabled) || $product->reviews_count === 0, 'mt-1' => ! empty($reviewsEnabled) && $product->reviews_count > 0])>
        <a href="{{ $product->url() }}" class="after:absolute after:inset-0">{{ $product->name }}</a>
    </h3>
    @if ($cardMaker)
        <p class="text-xs text-muted">{{ $cardMaker }}</p>
    @endif
    @if ($cardSizes && $cardSizes->isNotEmpty())
        <div class="mt-2 flex flex-wrap gap-1.5">
            @foreach ($cardSizes as $cardSize)
                <span @class(['rounded-lg border px-2 py-0.5 text-xs', 'border-[var(--es-ink)] font-semibold' => $loop->first, 'border-line text-muted' => ! $loop->first])>{{ $cardSize->value }}</span>
            @endforeach
        </div>
    @endif

    <div class="mt-auto flex items-end justify-between gap-2 pt-3">
        <p class="min-w-0">
            @if ($cardCompareAt)<del class="mr-1 text-sm text-muted">{{ format_price($cardCompareAt) }}</del>@endif
            <span class="font-head text-xl">{{ $cardPrice === null ? '' : ($cardFrom ? __('theme::shop.price_from', ['price' => format_price($cardPrice)]) : format_price($cardPrice)) }}</span>
            @if ($cardPrice !== null && $product::showsNetPrices())<span class="text-xs text-muted">{{ __('theme::shop.excl_vat') }}</span>@endif
            @if ($cardUnitPrice = $product->unitPriceIn())
                <span class="block text-[11px] text-muted">{{ __('theme::shop.unit_price', ['price' => format_price($product->displayPrice($cardUnitPrice)), 'unit' => __('theme::shop.units.'.$product->unit())]) }}</span>
            @endif
            @if ($cardStatus && ! $cardAvailable)
                <span class="mt-1 flex items-center gap-1.5 text-xs text-muted"><span class="size-1.5 rounded-full" style="background: {{ $cardStatus['color'] }}"></span>{{ $cardStatus['text'] }}</span>
            @endif
        </p>
        @if ($cardQuickAdd)
            <form method="POST" action="{{ route('cart.add') }}" class="relative z-20 shrink-0" data-ajax-cart>
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <button type="submit" class="btn btn-ac !gap-1 !px-3.5 !py-2 text-sm" aria-label="{{ __('theme::esencia.add_to_cart_label', ['name' => $product->name]) }}">
                    <span class="cart-icon-default flex items-center gap-1">@include('theme::partials.icon', ['name' => 'plus', 'class' => 'size-4', 'stroke' => 2.5])<span class="hidden sm:inline">{{ __('theme::esencia.add_to_cart') }}</span></span>
                    <span class="cart-icon-done">@include('theme::partials.icon', ['name' => 'check', 'class' => 'size-4', 'stroke' => 2.5])</span>
                </button>
            </form>
        @elseif ($cardHasVariants && $cardAvailable)
            <a href="{{ $product->url() }}" class="btn btn-secondary relative z-20 shrink-0 !px-3.5 !py-2 text-sm">{{ __('theme::esencia.choose') }}</a>
        @endif
    </div>
</article>
