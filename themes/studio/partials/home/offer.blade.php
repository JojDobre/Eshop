{{-- Poukážky a kozmetika: tmavá karta darčekovej poukážky a ukážka produktov z obchodu. --}}
@php
    $voucherLink = trim((string) $themeOptions->get('voucher_link'));
    $voucherImage = $themeOptions->imageUrl('voucher_image');
    $shopLink = trim((string) $themeOptions->get('shop_link'));
    $offerProducts = ! empty($latestProducts) ? $latestProducts->take(2) : collect();
@endphp
@if ($voucherLink !== '' || $offerProducts->isNotEmpty())
    <section @class([$wrap, 'grid gap-6 pb-16 sm:pb-20', 'lg:grid-cols-[1.1fr_.9fr]' => $voucherLink !== '' && $offerProducts->isNotEmpty()])>
        @if ($voucherLink !== '')
            <div @class(['relative overflow-hidden rounded-[2.5rem] bg-ink text-white', 'lg:order-2' => $reverse])>
                @if ($voucherImage)
                    <img {{ responsive_img($voucherImage, '(min-width: 1024px) 55vw, 95vw') }} alt="" class="absolute inset-0 size-full object-cover opacity-40" loading="lazy">
                @endif
                <div class="relative max-w-md p-8 sm:p-12">
                    <p class="eyebrow !text-gold">{{ __('theme::studio.voucher_kicker') }}</p>
                    <h2 class="studio-h2 mt-3">{{ $opt('voucher_title') }}</h2>
                    <p class="mt-4 text-white/85">{{ $opt('voucher_text') }}</p>
                    <a href="{{ $voucherLink }}" class="btn btn-light mt-7">{{ __('theme::studio.voucher_button') }}</a>
                </div>
            </div>
        @endif

        @if ($offerProducts->isNotEmpty())
            <div class="rounded-[2.5rem] bg-white p-6 sm:p-8">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p class="eyebrow">{{ __('theme::studio.shop_kicker') }}</p>
                        <p class="mt-2 font-serif text-3xl leading-tight">{{ $opt('shop_title') }}</p>
                    </div>
                    @if ($shopLink !== '')
                        <a href="{{ $shopLink }}" class="whitespace-nowrap text-sm font-semibold text-pine">{{ __('theme::studio.shop_link') }} →</a>
                    @endif
                </div>
                <div class="mt-6 grid grid-cols-2 gap-4">
                    @foreach ($offerProducts as $offerProduct)
                        @php($offerPrice = $offerProduct->displayPrice($offerProduct->priceFrom()))
                        <a href="{{ $offerProduct->url() }}" class="studio-zoom group block">
                            <span class="block aspect-square overflow-hidden rounded-2xl bg-stone">
                                @include('theme::partials.product-image', ['url' => $offerProduct->mainImageUrl(), 'alt' => $offerProduct->name, 'class' => ''])
                            </span>
                            <span class="mt-2 block text-sm font-semibold group-hover:text-pine">{{ $offerProduct->name }}</span>
                            @if ($offerPrice !== null)
                                <span class="text-sm text-muted">{{ $offerProduct->hasVaryingPrices() ? __('theme::shop.price_from', ['price' => format_price($offerPrice)]) : format_price($offerPrice) }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </section>
@endif
