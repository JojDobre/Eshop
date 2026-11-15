{{--
    Karta produktu Butiku: fotka na výšku, pri prejdení myšou druhá fotka a rýchle pridanie podľa veľkosti
    (vypredané veľkosti sú prečiarknuté), názov a cena, farby ako bodky.
--}}
@php
    $cardPrice = $product->displayPrice($product->priceFrom());
    $cardFrom = $product->hasVaryingPrices();
    $cardCompareAt = $product->displayPrice($product->compareAtPriceIn());
    $cardAvailable = $product->isAvailable();
    $cardStatus = $product->displayAvailability();
    $cardLabels = collect($product->displayLabels($cardPrice, $cardCompareAt))->unique(fn ($label) => mb_strtolower($label['text']))->take(2)->values()->all();
    $cardImages = $product->imageUrls();
    $cardSecond = $themeOptions->get('second_image', true) ? ($cardImages[1] ?? null) : null;
    $cardColors = collect();
    $cardSizes = collect();

    if ($product->hasVariants()) {
        $product->loadMissing(['options.values', 'variants.values']);
        $isColor = fn ($value) => (bool) preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $value->color);
        $colorOption = $product->options->first(fn ($option) => $option->values->contains($isColor));
        $sizeOption = $product->options->first(fn ($option) => $option->id !== $colorOption?->id);
        $cardColors = $colorOption?->values->filter($isColor)->take(5) ?? collect();
        $firstColor = $colorOption?->values->first();

        // Rýchle pridanie: pre každú veľkosť variant v prvej farbe (ak produkt farby má).
        if ($sizeOption && $product->options->count() <= 2 && Route::has('cart.add')) {
            $cardSizes = $sizeOption->values->map(function ($size) use ($product, $firstColor) {
                $variant = $product->variants->first(fn ($variant) => $variant->is_active
                    && $variant->values->contains('id', $size->id)
                    && ($firstColor === null || $variant->values->contains('id', $firstColor->id)));

                return ['label' => $size->value, 'variant' => $variant, 'available' => $variant !== null && $variant->setRelation('product', $product)->isAvailable()];
            })->filter(fn ($size) => $size['variant'] !== null)->take(7);
        }
    }

    $cardQuickAdd = $cardAvailable && ! $product->hasVariants() && Route::has('cart.add');
@endphp
<article @class(['group relative', 'opacity-80' => ! $cardAvailable])>
    <div class="relative aspect-[3/4] overflow-hidden bg-soft">
        @include('theme::partials.product-image', ['url' => $cardImages[0] ?? null, 'alt' => $product->name, 'class' => 'absolute inset-0 transition duration-500'.($cardSecond ? ' group-hover:opacity-0' : '')])
        @if ($cardSecond)
            <img {{ responsive_img($cardSecond, '(min-width: 1024px) 25vw, 50vw') }} alt="" loading="lazy" class="absolute inset-0 size-full object-cover opacity-0 transition duration-500 group-hover:opacity-100">
        @endif

        @if ($cardLabels !== [])
            <div class="absolute left-3 top-3 z-10 flex flex-col items-start gap-1">
                @foreach ($cardLabels as $cardLabel)
                    <span @class(['px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider', 'bg-ac text-onac' => $cardLabel['style'] === null && $cardLabel['kind'] === 'sale', 'bg-white text-ink' => $cardLabel['style'] === null && $cardLabel['kind'] !== 'sale']) @if ($cardLabel['style']) style="{{ $cardLabel['style'] }}" @endif>{{ $cardLabel['text'] }}</span>
                @endforeach
            </div>
        @endif

        @if ($cardSizes->isNotEmpty())
            <div class="absolute inset-x-3 bottom-3 z-20 hidden bg-white/95 p-3 transition sm:block sm:translate-y-2 sm:opacity-0 sm:group-hover:translate-y-0 sm:group-hover:opacity-100 sm:group-focus-within:translate-y-0 sm:group-focus-within:opacity-100">
                <p class="cap text-muted">{{ __('theme::butik.quick_add') }}</p>
                <div class="mt-2 flex flex-wrap justify-between gap-1 text-sm font-semibold">
                    @foreach ($cardSizes as $cardSize)
                        @if ($cardSize['available'])
                            <form method="POST" action="{{ route('cart.add') }}" data-ajax-cart>
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                <input type="hidden" name="variant_id" value="{{ $cardSize['variant']->id }}">
                                <button type="submit" class="px-1 hover:text-ac" aria-label="{{ __('theme::butik.quick_add_size', ['size' => $cardSize['label']]) }}: {{ $product->name }}">
                                    <span class="cart-icon-default">{{ $cardSize['label'] }}</span>
                                    <span class="cart-icon-done">✓</span>
                                </button>
                            </form>
                        @else
                            <span class="px-1 text-muted line-through" title="{{ __('theme::butik.sold_out_size', ['size' => $cardSize['label']]) }}">{{ $cardSize['label'] }}</span>
                        @endif
                    @endforeach
                </div>
            </div>
        @elseif ($cardQuickAdd)
            <form method="POST" action="{{ route('cart.add') }}" class="absolute inset-x-3 bottom-3 z-20 hidden transition sm:block sm:translate-y-2 sm:opacity-0 sm:group-hover:translate-y-0 sm:group-hover:opacity-100 sm:group-focus-within:translate-y-0 sm:group-focus-within:opacity-100" data-ajax-cart>
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <button type="submit" class="btn btn-light w-full !py-3" aria-label="{{ __('theme::butik.add_to_cart_label', ['name' => $product->name]) }}">
                    <span class="cart-icon-default">{{ __('theme::butik.add_to_cart') }}</span>
                    <span class="cart-icon-done">✓</span>
                </button>
            </form>
        @endif
    </div>

    <div class="mt-3 flex items-start justify-between gap-3">
        <h3 class="text-[15px] font-medium leading-snug">
            <a href="{{ $product->url() }}" class="after:absolute after:inset-0 after:z-10">{{ $product->name }}</a>
        </h3>
        <p class="whitespace-nowrap text-[15px] font-semibold">
            @if ($cardCompareAt)<del class="mr-1.5 font-normal text-muted">{{ format_price($cardCompareAt) }}</del>@endif
            <span @class(['text-ac' => $cardCompareAt])>{{ $cardPrice === null ? '' : ($cardFrom ? __('theme::shop.price_from', ['price' => format_price($cardPrice)]) : format_price($cardPrice)) }}</span>
        </p>
    </div>
    @if ($cardColors->isNotEmpty())
        <p class="mt-2 flex items-center gap-1.5">
            <span class="sr-only">{{ __('theme::butik.colors') }}: {{ $cardColors->pluck('value')->implode(', ') }}</span>
            @foreach ($cardColors as $cardColor)
                <span class="size-3.5 rounded-full ring-1 ring-black/15" style="background: {{ $cardColor->color }}" aria-hidden="true"></span>
            @endforeach
        </p>
    @endif
    @if ($cardStatus && ! $cardAvailable)
        <p class="mt-1 flex items-center gap-1.5 text-xs text-muted"><span class="size-1.5 rounded-full" style="background: {{ $cardStatus['color'] }}"></span>{{ $cardStatus['text'] }}</p>
    @endif
</article>
