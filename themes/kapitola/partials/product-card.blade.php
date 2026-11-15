{{--
    Karta produktu Kapitoly: obálka ako kniha na jemnom podklade (štítok, pri digitálnych produktoch „Na stiahnutie“),
    názov, krátky popis (napr. autor), cena a tlačidlo do košíka.
--}}
@php
    $cardPrice = $product->displayPrice($product->priceFrom());
    $cardFrom = $product->hasVaryingPrices();
    $cardCompareAt = $product->displayPrice($product->compareAtPriceIn());
    $cardAvailable = $product->isAvailable();
    $cardStatus = $product->displayAvailability();
    $cardLabel = collect($product->displayLabels($cardPrice, $cardCompareAt))->first();
    $cardSubtitle = trim(strip_tags((string) $product->short_description));
    $cardQuickAdd = $cardAvailable && ! $product->hasVariants() && Route::has('cart.add');
@endphp
<article @class(['group relative flex flex-col', 'opacity-80' => ! $cardAvailable])>
    <div class="relative flex aspect-[4/5] items-center justify-center rounded-2xl bg-soft px-[14%] py-[10%]">
        @if ($image = $product->mainImageUrl())
            <img {{ responsive_img($image, '(min-width: 1024px) 18vw, 40vw') }} alt="{{ $product->name }}" loading="lazy" class="book max-h-full w-auto max-w-full object-contain transition duration-300 group-hover:-translate-y-1.5">
        @else
            @include('theme::partials.product-image', ['url' => null, 'alt' => $product->name])
        @endif
        @if ($cardLabel)
            <span @class(['absolute left-3 top-3 max-w-[calc(100%-1.5rem)] truncate rounded-full px-2.5 py-1 text-[11px] font-bold', 'bg-ac text-onac' => $cardLabel['style'] === null && $cardLabel['kind'] === 'sale', 'bg-ac2 text-onac2' => $cardLabel['style'] === null && $cardLabel['kind'] !== 'sale']) @if ($cardLabel['style']) style="{{ $cardLabel['style'] }}" @endif>{{ $cardLabel['text'] }}</span>
        @endif
    </div>
    @if ($product->isDigital())
        <p class="mt-3 flex items-center gap-1 text-xs font-bold uppercase tracking-wider text-ac">@include('theme::partials.icon', ['name' => 'download', 'class' => 'size-3.5', 'stroke' => 2.4]){{ __('theme::kapitola.download') }}</p>
    @endif
    <h3 @class(['font-head text-lg leading-snug', 'mt-1' => $product->isDigital(), 'mt-3' => ! $product->isDigital()])>
        <a href="{{ $product->url() }}" class="after:absolute after:inset-0">{{ $product->name }}</a>
    </h3>
    @if ($cardSubtitle !== '')
        <p class="line-clamp-1 text-sm text-muted">{{ $cardSubtitle }}</p>
    @endif
    <div class="mt-auto flex items-center justify-between gap-2 pt-2">
        <p class="min-w-0 font-bold">
            @if ($cardCompareAt)<del class="mr-1 text-sm font-normal text-muted">{{ format_price($cardCompareAt) }}</del>@endif
            <span class="whitespace-nowrap">{{ $cardPrice === null ? '' : ($cardFrom ? __('theme::shop.price_from', ['price' => format_price($cardPrice)]) : format_price($cardPrice)) }}</span>
            @if ($cardStatus && ! $cardAvailable)
                <span class="mt-0.5 flex items-center gap-1.5 text-xs font-normal text-muted"><span class="size-1.5 rounded-full" style="background: {{ $cardStatus['color'] }}"></span>{{ $cardStatus['text'] }}</span>
            @endif
        </p>
        @if ($cardQuickAdd)
            <form method="POST" action="{{ route('cart.add') }}" class="relative z-10 shrink-0" data-ajax-cart>
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <button type="submit" class="grid size-10 place-items-center rounded-xl bg-ac text-onac transition hover:bg-[var(--kp-acd)]" aria-label="{{ __('theme::kapitola.add_to_cart_label', ['name' => $product->name]) }}">
                    <span class="cart-icon-default">@include('theme::partials.icon', ['name' => 'plus', 'class' => 'size-5', 'stroke' => 2.2])</span>
                    <span class="cart-icon-done">@include('theme::partials.icon', ['name' => 'check', 'class' => 'size-5', 'stroke' => 2.4])</span>
                </button>
            </form>
        @elseif ($cardAvailable)
            <a href="{{ $product->url() }}" class="relative z-10 shrink-0 rounded-xl bg-soft px-3 py-2 text-sm font-bold">{{ __('theme::kapitola.choose') }}</a>
        @endif
    </div>
</article>
