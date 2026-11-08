@extends('theme::layouts.app')

@php
    $price = $product->priceIn();
    $images = $product->imageUrls();
    $available = $price !== null && $product->isAvailable();
    $maxQuantity = $product->maxPurchasableQuantity();
    // Predobjednávka: tovar nie je na sklade, ale dá sa objednať vopred.
    $preorder = $available && $product->isPreorder();
    $preorderText = $product->preorder_available_at
        ? __('theme::shop.preorder_from', ['date' => $product->preorder_available_at->format(app()->getLocale() === 'en' ? 'j M Y' : 'j. n. Y')])
        : __('theme::shop.preorder');
    $compareAt = $product->compareAtPriceIn();
    $lowest30 = $compareAt ? $product->lowestPriceIn30Days() : null;
    // B2B skupina: ceny hlavne bez DPH, cena s DPH pod nimi.
    $netPrices = $product::showsNetPrices();
    $show = fn (?int $gross) => $product->displayPrice($gross);
    $quantityDiscounts = $product->applicableQuantityDiscounts();
    $manufacturer = $product->manufacturer;
    $safetyInfo = $product->safety_info ?: $manufacturer?->safety_info;
    // SEO šablóny (Nastavenia → SEO), ak produkt nemá vlastný titulok a popis.
    $seo = \App\Settings\SeoSettings::current();
    $seoVariables = ['name' => $product->name, 'category' => $category?->name ?? $product->categories->first()?->name, 'shop' => $shop->shop_name,
        'price' => $price !== null ? format_price($price) : null, 'manufacturer' => $manufacturer?->name];
@endphp

@section('title', $product->meta_title ?: (\App\Settings\SeoSettings::render($seo?->product_title, $seoVariables) ?? $product->name))
@section('meta_description', $product->meta_description ?: (\App\Settings\SeoSettings::render($seo?->product_description, $seoVariables) ?: $product->short_description ?: \Illuminate\Support\Str::limit(strip_tags((string) $product->description), 160)))
@section('canonical', $product->url())
@if ($images)
    @section('og_image', $images[0])
@endif

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            window.Alpine.data('variantPicker', (data) => ({
                options: data.options,
                variants: data.variants,
                selected: {},
                variant: null,
                init() {
                    // Ak je len jedna hodnota možnosti, vyberie sa automaticky.
                    this.options.forEach((option) => { if (option.values.length === 1) this.selected[option.id] = option.values[0].id; });
                    this.resolve();
                },
                select(optionId, valueId) {
                    this.selected[optionId] = this.selected[optionId] === valueId ? undefined : valueId;
                    this.resolve();
                },
                resolve() {
                    const chosen = Object.values(this.selected).filter(Boolean);
                    this.variant = chosen.length === this.options.length
                        ? this.variants.find((variant) => chosen.every((id) => variant.values.includes(id))) || null
                        : null;
                    this.$dispatch('variant-images', this.variant && this.variant.images ? this.variant.images : []);
                },
                isValueAvailable(optionId, valueId) {
                    return this.variants.some((variant) => variant.available && variant.values.includes(valueId)
                        && Object.entries(this.selected).every(([id, selectedValue]) => ! selectedValue || Number(id) === optionId || variant.values.includes(selectedValue)));
                },
                selectedName(option) {
                    const value = option.values.find((value) => value.id === this.selected[option.id]);
                    return value ? value.value : '';
                },
            }));
        });
    </script>
@endpush

@push('head')
    <script type="application/ld+json">{!! json_encode(array_filter([
        // Kľúč je zložený z dvoch častí, inak by ho Blade spracoval ako svoju direktívu.
        '@'.'context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->name,
        'description' => $product->short_description ?: strip_tags((string) $product->description),
        'sku' => $product->sku,
        'image' => $images ?: null,
        // Priemerné hodnotenie (iba schválené hodnotenia z modulu Hodnotenia).
        'aggregateRating' => ! empty($productReviews['count']) ? [
            '@type' => 'AggregateRating',
            'ratingValue' => number_format($productReviews['average'], 1, '.', ''),
            'reviewCount' => $productReviews['count'],
        ] : null,
        'offers' => $price === null ? null : [
            '@type' => 'Offer',
            'price' => \App\Core\Support\Price::toDecimal($price, app(\App\Core\Support\CurrencyContext::class)->current()),
            'priceCurrency' => app(\App\Core\Support\CurrencyContext::class)->current(),
            'availability' => $available ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'url' => $product->url(),
        ],
    ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
    <div class="container-shop py-8">
        @include('theme::partials.breadcrumbs', ['items' => [
            ...($category ? [...array_map(fn ($ancestor) => [$ancestor->name, $ancestor->url()], $category->ancestors()), [$category->name, $category->url()]] : []),
            [$product->name, null],
        ]])

        <div class="mt-8 grid gap-10 lg:grid-cols-2 lg:gap-16">
            <div x-data="{ active: 0, images: @js($images) }" @variant-images.window="images = $event.detail.length ? $event.detail : @js($images); active = 0">
                <div class="aspect-square overflow-hidden rounded-2xl bg-zinc-100">
                    @if ($images)
                        <template x-for="(image, index) in images" :key="image">
                            <img :src="image" alt="{{ $product->name }}" x-show="active === index" class="h-full w-full object-cover">
                        </template>
                        <noscript><img src="{{ $images[0] }}" alt="{{ $product->name }}" class="h-full w-full object-cover"></noscript>
                    @else
                        @include('theme::partials.product-image', ['url' => null, 'alt' => $product->name])
                    @endif
                </div>
                <div class="mt-4 grid grid-cols-5 gap-3" x-show="images.length > 1" @if (count($images) <= 1) x-cloak @endif>
                    <template x-for="(image, index) in images" :key="'thumb-' + image">
                        <button type="button" @click="active = index" class="aspect-square overflow-hidden rounded-lg bg-zinc-100 ring-offset-2"
                                :class="active === index ? 'ring-2 ring-primary' : 'opacity-70 hover:opacity-100'"
                                :aria-label="'{{ __('theme::shop.show_image', ['number' => '']) }}' + (index + 1)">
                            <img :src="image" alt="" loading="lazy" class="h-full w-full object-cover">
                        </button>
                    </template>
                </div>
            </div>

            <div>
                <h1 class="text-3xl font-semibold tracking-tight">{{ $product->name }}</h1>
                @if (! empty($productReviews['count']))
                    <a href="#hodnotenia" class="mt-2 inline-flex items-center gap-2 text-sm text-zinc-600 hover:text-zinc-900">
                        @include('theme::partials.rating-stars', ['rating' => $productReviews['average']])
                        <span>{{ trans_choice('reviews::shop.count', $productReviews['count'], ['count' => $productReviews['count']]) }}</span>
                    </a>
                @endif
                @if ($product->sku)
                    <p class="mt-2 text-sm text-zinc-500">{{ __('theme::shop.sku') }}: {{ $product->sku }}</p>
                @endif

                <div class="mt-6 flex flex-wrap items-baseline gap-3">
                    @if ($variantData && $product->hasVaryingPrices())
                        <p class="text-3xl font-semibold">{{ __('theme::shop.price_from', ['price' => format_price($show($product->priceFrom()))]) }}</p>
                    @else
                        <p class="text-3xl font-semibold">{{ $price !== null ? format_price($show($product->priceFrom() ?? $price)) : __('theme::shop.price_unavailable') }}</p>
                    @endif
                    @if ($compareAt)
                        <del class="text-lg text-zinc-500">{{ format_price($show($compareAt)) }}</del>
                        <span class="rounded-full bg-primary px-2.5 py-0.5 text-sm font-semibold text-primary-foreground">−{{ (int) round((1 - $price / $compareAt) * 100) }} %</span>
                    @endif
                </div>
                @if ($unitPrice = $product->unitPriceIn())
                    <p class="mt-1 text-sm text-zinc-500">{{ __('theme::shop.unit_price', ['price' => format_price($show($unitPrice)), 'unit' => __('theme::shop.units.'.$product->unit())]) }}</p>
                @endif
                @if ($lowest30)
                    <p class="mt-1 text-sm text-zinc-500">{{ __('theme::shop.lowest_price_30_days', ['price' => format_price($show($lowest30))]) }}</p>
                @endif
                @if ($netPrices && $price !== null)
                    <p class="mt-1 text-sm text-zinc-500">{{ __('theme::shop.price_excludes_vat', ['price' => format_price($product->priceFrom() ?? $price)]) }}</p>
                @elseif ($vatPayer)
                    <p class="mt-1 text-sm text-zinc-500">{{ __('theme::shop.price_includes_vat') }}</p>
                @endif
                @if ($quantityDiscounts->isNotEmpty() && $price !== null)
                    <div class="mt-4 rounded-lg border border-zinc-200 p-3 text-sm">
                        <p class="font-medium">{{ __('theme::shop.quantity_discounts') }}</p>
                        <ul class="mt-1 space-y-0.5 text-zinc-600">
                            @foreach ($quantityDiscounts as $discount)
                                <li>{{ __('theme::shop.quantity_discount_row', ['quantity' => $discount->min_quantity, 'percent' => rtrim(rtrim(number_format($discount->discount_percent, 2, ',', ''), '0'), ','), 'price' => format_price($show($product->withQuantityDiscount($price, $discount->min_quantity)))]) }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($preorder)
                    <p class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-amber-700">
                        <span class="size-2 rounded-full bg-amber-500" aria-hidden="true"></span>
                        {{ $preorderText }}
                    </p>
                @else
                    <p @class(['mt-4 inline-flex items-center gap-2 text-sm font-medium', 'text-emerald-700' => $available, 'text-zinc-500' => ! $available]) @if ($variantData) x-data x-show="false" @endif>
                        <span @class(['size-2 rounded-full', 'bg-emerald-500' => $available, 'bg-zinc-400' => ! $available]) aria-hidden="true"></span>
                        {{ $available ? ($maxQuantity !== null && $maxQuantity <= 5 ? __('theme::shop.in_stock_few', ['count' => $maxQuantity]) : __('theme::shop.in_stock')) : __('theme::shop.sold_out') }}
                    </p>
                @endif

                {{-- Expirácia (šarže): dátum spotreby tovaru, ktorý zákazník dostane, a zľava pri krátkej expirácii. --}}
                @if ($available && ! $product->hasVariants() && $product->track_stock)
                    @php($stockLots = app(\Modules\Catalog\StockLots::class))
                    @php($expiryDiscount = $stockLots->expiryDiscountPercent($product, null))
                    @if ($expiryDiscount > 0)
                        <p class="mt-2 text-sm font-semibold text-amber-700">{{ __('theme::shop.short_expiry', ['percent' => $expiryDiscount]) }}</p>
                    @endif
                    @if ($stockLots->expirationEnabled() && rescue(fn () => app(\App\Settings\CatalogSettings::class)->expiration_show_date, false, false) && ($nextExpiry = $stockLots->nextExpiry($product, null)))
                        <p class="mt-1 text-sm text-zinc-500">{{ __('theme::shop.best_before', ['date' => $nextExpiry->format(app()->getLocale() === 'en' ? 'j M Y' : 'j. n. Y')]) }}</p>
                    @endif
                @endif

                @if ($product->short_description)
                    <p class="mt-6 text-zinc-600">{{ $product->short_description }}</p>
                @endif

                @if ($product->isBundle() && $product->bundleItems->isNotEmpty())
                    @php($componentsPrice = $product->bundleComponentsPrice())
                    <div class="mt-6 rounded-lg border border-zinc-200 p-4">
                        <h2 class="text-sm font-semibold">{{ __('theme::shop.bundle_contents') }}</h2>
                        <ul class="mt-2 space-y-1 text-sm text-zinc-600">
                            @foreach ($product->bundleItems as $item)
                                <li>{{ $item->quantity }} × {{ $item->name() }}</li>
                            @endforeach
                        </ul>
                        @if ($price !== null && $componentsPrice !== null && $componentsPrice > $price)
                            <p class="mt-2 text-sm font-semibold text-primary">{{ __('theme::shop.bundle_saving', ['amount' => format_price($componentsPrice - $price)]) }}</p>
                        @endif
                    </div>
                @endif

                @if ($variantData && Route::has('cart.add'))
                    <form method="POST" action="{{ route('cart.add') }}" class="mt-8 space-y-6" x-data="variantPicker(@js($variantData))">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <input type="hidden" name="variant_id" :value="variant ? variant.id : ''">
                        @include('theme::partials.product-addons')

                        <template x-for="option in options" :key="option.id">
                            <fieldset>
                                <legend class="text-sm font-medium">
                                    <span x-text="option.name"></span><span class="font-normal text-zinc-500" x-show="selectedName(option)" x-text="': ' + selectedName(option)"></span>
                                </legend>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <template x-for="value in option.values" :key="value.id">
                                        <button type="button" @click="select(option.id, value.id)"
                                                :aria-pressed="(selected[option.id] === value.id).toString()"
                                                :disabled="! isValueAvailable(option.id, value.id)"
                                                :title="value.value"
                                                :class="[
                                                    selected[option.id] === value.id ? 'border-primary ring-1 ring-primary' : 'border-zinc-300 hover:border-zinc-500',
                                                    isValueAvailable(option.id, value.id) ? '' : 'opacity-40 line-through',
                                                    value.color ? 'size-10 rounded-full p-1' : 'min-w-12 rounded-lg px-3 py-2 text-sm',
                                                ]"
                                                class="border bg-white transition disabled:cursor-not-allowed">
                                            <span x-show="value.color" class="block size-full rounded-full" :style="'background:' + value.color"></span>
                                            <span x-show="! value.color" x-text="value.value"></span>
                                            <span class="sr-only" x-show="value.color" x-text="value.value"></span>
                                        </button>
                                    </template>
                                </div>
                            </fieldset>
                        </template>

                        <p class="text-sm" x-show="variant" x-cloak>
                            <span class="font-semibold" x-text="variant && variant.price"></span>
                            <span class="ml-2" :class="variant && variant.available ? 'text-emerald-700' : 'text-zinc-500'"
                                  x-text="variant && variant.available ? (variant.preorder ? @js($preorderText) : '{{ __('theme::shop.in_stock') }}') : '{{ __('theme::shop.sold_out') }}'"></span>
                        </p>

                        <div class="flex gap-3">
                            <label for="quantity" class="sr-only">{{ __('theme::shop.quantity') }}</label>
                            <input id="quantity" type="number" name="quantity" value="1" min="1" :max="variant && variant.max !== null ? variant.max : null" class="input w-20 text-center">
                            <button type="submit" class="btn btn-primary flex-1 sm:flex-none sm:px-10" :disabled="! variant || ! variant.available">
                                <span x-text="variant ? (variant.preorder ? '{{ __('theme::shop.preorder_button') }}' : '{{ __('theme::shop.add_to_cart') }}') : '{{ __('theme::shop.choose_variant') }}'">{{ __('theme::shop.add_to_cart') }}</span>
                            </button>
                        </div>
                    </form>
                @elseif ($available && Route::has('cart.add'))
                    <form method="POST" action="{{ route('cart.add') }}" class="mt-8 space-y-4">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        @include('theme::partials.product-addons')
                        <div class="flex gap-3">
                            <label for="quantity" class="sr-only">{{ __('theme::shop.quantity') }}</label>
                            <input id="quantity" type="number" name="quantity" value="1" min="1" @if ($maxQuantity !== null) max="{{ $maxQuantity }}" @endif class="input w-20 text-center">
                            <button type="submit" class="btn btn-primary flex-1 sm:flex-none sm:px-10">{{ $preorder ? __('theme::shop.preorder_button') : __('theme::shop.add_to_cart') }}</button>
                        </div>
                    </form>
                @endif

                @isset($productReservation)
                    @include('reservations::product-reservation')
                @endisset

                @if (! empty($compareEnabled))
                    @include('theme::partials.compare-button', ['product' => $product])
                @endif

                @if ($product->description)
                    <div class="prose prose-zinc mt-10 max-w-none border-t border-zinc-100 pt-8">{{ safe_html($product->description) }}</div>
                @endif

                @if ($parameters = $product->visibleParameters())
                    <section class="mt-10 border-t border-zinc-100 pt-8" aria-labelledby="parameters">
                        <h2 id="parameters" class="font-semibold">{{ __('catalog::shop.parameters') }}</h2>
                        <dl class="mt-4 divide-y divide-zinc-100 text-sm">
                            @foreach ($parameters as $label => $value)
                                <div class="grid grid-cols-2 gap-4 py-2.5">
                                    <dt class="text-zinc-500">{{ $label }}</dt>
                                    <dd class="text-zinc-900">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </section>
                @endif

                @if ($manufacturer || $safetyInfo)
                    <details class="group mt-8 border-t border-zinc-100 pt-6 text-sm">
                        <summary class="cursor-pointer list-none font-medium marker:hidden">
                            <span class="inline-flex items-center gap-2">{{ __('theme::shop.product_safety') }}
                                <svg class="size-4 transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                            </span>
                        </summary>
                        <div class="mt-4 space-y-4 text-zinc-600">
                            @if ($manufacturer)
                                <div>
                                    <p class="font-medium text-zinc-900">{{ __('theme::shop.manufacturer') }}</p>
                                    <p>{{ $manufacturer->name }}</p>
                                    @if ($manufacturer->address)<p>{!! nl2br(e($manufacturer->address)) !!}</p>@endif
                                    @if ($manufacturer->email)<p><a href="mailto:{{ $manufacturer->email }}" class="underline">{{ $manufacturer->email }}</a></p>@endif
                                    @if ($manufacturer->website)<p><a href="{{ $manufacturer->website }}" class="underline" rel="nofollow noopener" target="_blank">{{ $manufacturer->website }}</a></p>@endif
                                </div>
                                @if ($manufacturer->eu_responsible_person)
                                    <div>
                                        <p class="font-medium text-zinc-900">{{ __('theme::shop.eu_responsible_person') }}</p>
                                        <p>{!! nl2br(e($manufacturer->eu_responsible_person)) !!}</p>
                                    </div>
                                @endif
                            @endif
                            @if ($safetyInfo)
                                <div>
                                    <p class="font-medium text-zinc-900">{{ __('theme::shop.safety_information') }}</p>
                                    <p>{!! nl2br(e($safetyInfo)) !!}</p>
                                </div>
                            @endif
                        </div>
                    </details>
                @endif
            </div>
        </div>

        @if ($relatedProducts->isNotEmpty())
            <section class="mt-20" aria-labelledby="related">
                <h2 id="related" class="text-xl font-semibold tracking-tight">{{ __('theme::shop.related_products') }}</h2>
                <div class="mt-6">@include('theme::partials.product-grid', ['products' => $relatedProducts])</div>
            </section>
        @endif
    </div>
    @isset($productReviews)
        <div class="container-shop">@include('theme::partials.reviews')</div>
    @endisset
    @include('theme::partials.related-posts', ['relatedPosts' => $relatedPosts ?? null])
@endsection
