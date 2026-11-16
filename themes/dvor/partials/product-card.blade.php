{{--
    Karta produktu Dvora: fotka v zaoblenom ráme so štítkom, názov, krátky popis s lístkom (napr. „zbierané dnes“),
    cena na cenovke s balením (z typu produktu, napr. „/ 250 g“) a okrúhle tlačidlo do košíka.
--}}
@php
    $cardPrice = $product->displayPrice($product->priceFrom());
    $cardFrom = $product->hasVaryingPrices();
    $cardCompareAt = $product->displayPrice($product->compareAtPriceIn());
    $cardAvailable = $product->isAvailable();
    $cardStatus = $product->displayAvailability();
    $cardLabel = collect($product->displayLabels($cardPrice, $cardCompareAt))->first();
    $cardNote = trim(strip_tags((string) $product->short_description));
    $cardPack = trim(view('theme::partials.pack', ['product' => $product])->render());
    $cardQuickAdd = $cardAvailable && ! $product->hasVariants() && Route::has('cart.add');
@endphp
<article @class(['dv-card dv-zoom group relative flex flex-col p-3', 'opacity-80' => ! $cardAvailable])>
    <div class="relative aspect-[5/4] overflow-hidden rounded-[1.1rem] bg-soft">
        @include('theme::partials.product-image', ['url' => $product->mainImageUrl(), 'alt' => $product->name])
        @if ($cardLabel)
            <span @class(['absolute left-3 top-3 max-w-[calc(100%-1.5rem)] truncate rounded-full px-2.5 py-1 text-[11px] font-extrabold', 'bg-ac2 text-onac2' => $cardLabel['style'] === null && $cardLabel['kind'] === 'sale', 'bg-sun text-onsun' => $cardLabel['style'] === null && $cardLabel['kind'] !== 'sale']) @if ($cardLabel['style']) style="{{ $cardLabel['style'] }}" @endif>{{ $cardLabel['text'] }}</span>
        @endif
    </div>
    <div class="flex flex-1 flex-col px-1.5 pb-1 pt-3">
        <h3 class="font-head text-xl leading-tight">
            <a href="{{ $product->url() }}" class="after:absolute after:inset-0">{{ $product->name }}</a>
        </h3>
        @if ($cardNote !== '')
            <p class="mt-1 flex items-center gap-1.5 text-xs font-bold text-ac">@include('theme::partials.icon', ['name' => 'leaf', 'class' => 'size-3.5 shrink-0'])<span class="line-clamp-1">{{ $cardNote }}</span></p>
        @endif
        @if ($cardStatus && ! $cardAvailable)
            <p class="mt-1 flex items-center gap-1.5 text-xs text-muted"><span class="size-1.5 rounded-full" style="background: {{ $cardStatus['color'] }}"></span>{{ $cardStatus['text'] }}</p>
        @endif
        <div class="mt-auto flex items-center justify-between gap-2 pt-3">
            @if ($cardPrice !== null)
                <p class="pricetag min-w-0 text-[15px] font-extrabold">
                    @if ($cardCompareAt)<del class="text-xs font-normal text-muted">{{ format_price($cardCompareAt) }}</del>@endif
                    <span class="whitespace-nowrap">{{ $cardFrom ? __('theme::shop.price_from', ['price' => format_price($cardPrice)]) : format_price($cardPrice) }}</span>
                    @if ($cardPack !== '')<span class="whitespace-nowrap text-xs font-semibold text-muted">/ {{ $cardPack }}</span>@endif
                </p>
            @endif
            @if ($cardQuickAdd)
                <form method="POST" action="{{ route('cart.add') }}" class="relative z-10 shrink-0" data-ajax-cart>
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <button type="submit" class="grid size-10 place-items-center rounded-full bg-ac text-onac transition hover:bg-[var(--dv-acd)]" aria-label="{{ __('theme::dvor.add_to_cart_label', ['name' => $product->name]) }}">
                        <span class="cart-icon-default">@include('theme::partials.icon', ['name' => 'plus', 'class' => 'size-5', 'stroke' => 2.4])</span>
                        <span class="cart-icon-done">@include('theme::partials.icon', ['name' => 'check', 'class' => 'size-5', 'stroke' => 2.6])</span>
                    </button>
                </form>
            @elseif ($cardAvailable)
                <a href="{{ $product->url() }}" class="relative z-10 shrink-0 rounded-full bg-soft px-4 py-2 text-sm font-extrabold">{{ __('theme::dvor.choose') }}</a>
            @endif
        </div>
    </div>
</article>
