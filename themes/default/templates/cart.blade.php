@extends('theme::layouts.app')

@section('title', __('theme::shop.cart'))
@section('noindex', true)

@section('content')
    <div class="container-shop py-10">
        <h1 class="text-3xl font-semibold tracking-tight">{{ __('theme::shop.cart') }}</h1>

        @if ($summary->isEmpty())
            <div class="mt-10 rounded-2xl border border-dashed border-zinc-200 px-6 py-16 text-center">
                <p class="text-zinc-600">{{ __('theme::shop.cart_empty') }}</p>
                <a href="{{ route('storefront.home') }}" class="btn btn-primary mt-6">{{ __('theme::shop.continue_shopping') }}</a>
            </div>
        @else
            <div class="mt-8 grid gap-10 lg:grid-cols-3">
                <ul class="divide-y divide-zinc-100 border-y border-zinc-100 lg:col-span-2">
                    @foreach ($summary->lines as $line)
                        <li class="flex gap-4 py-5">
                            <a href="{{ $line->product->url() }}" class="size-20 shrink-0 overflow-hidden rounded-lg bg-zinc-100 sm:size-24">
                                @include('theme::partials.product-image', ['url' => $line->imageUrls()[0] ?? null, 'alt' => $line->name()])
                            </a>
                            <div class="flex flex-1 flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <a href="{{ $line->product->url() }}" class="font-medium hover:underline">{{ $line->product->name }}</a>
                                    @if ($line->variant)
                                        <p class="text-sm text-zinc-600">{{ $line->variant->name() }}</p>
                                    @endif
                                    @if ($line->isPreorder())
                                        <p class="text-sm font-medium text-amber-700">{{ __('theme::shop.preorder_line') }}</p>
                                    @endif
                                    @if ($line->addonsLabel())
                                        <p class="text-sm text-zinc-600">+ {{ $line->addonsLabel() }}</p>
                                    @endif
                                    <p class="mt-1 text-sm text-zinc-500">{{ $line->unitPrice !== null ? format_price($line->unitPrice) : '' }} / {{ __('theme::shop.piece') }}</p>
                                    @unless ($line->isAvailable)
                                        <p class="mt-1 text-sm font-medium text-red-600">{{ __('theme::shop.line_unavailable') }}</p>
                                    @endunless
                                </div>
                                <div class="flex items-center gap-4">
                                    <form method="POST" action="{{ route('cart.update', $line->item->id) }}" class="flex items-center gap-2">
                                        @csrf @method('PATCH')
                                        <label for="qty-{{ $line->item->id }}" class="sr-only">{{ __('theme::shop.quantity') }}</label>
                                        <input id="qty-{{ $line->item->id }}" type="number" name="quantity" value="{{ $line->quantity }}" min="0"
                                               @if ($line->maxQuantity() !== null) max="{{ $line->maxQuantity() }}" @endif
                                               class="input w-16 py-1.5 text-center" onchange="this.form.submit()">
                                        <noscript><button class="text-sm underline">{{ __('theme::shop.update') }}</button></noscript>
                                    </form>
                                    <span class="w-24 text-right font-medium">{{ format_price($line->total()) }}</span>
                                    <form method="POST" action="{{ route('cart.remove', $line->item->id) }}">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="rounded-md p-1.5 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700" aria-label="{{ __('theme::shop.remove', ['product' => $line->name()]) }}">
                                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <aside class="h-fit rounded-2xl bg-zinc-50 p-6">
                    <dl class="space-y-2">
                        <div class="flex justify-between text-base font-semibold">
                            <dt>{{ __('theme::shop.subtotal') }}</dt>
                            <dd>{{ format_price($summary->subtotal()) }}</dd>
                        </div>
                        @if (! empty($coupon))
                            <div class="flex justify-between text-sm text-emerald-700">
                                <dt>{{ __('theme::shop.discount_code', ['code' => $coupon->code]) }}</dt>
                                <dd>{{ $couponDiscount > 0 ? '−'.format_price($couponDiscount) : __('theme::shop.free_shipping') }}</dd>
                            </div>
                        @endif
                    </dl>
                    <p class="mt-1 text-sm text-zinc-500">{{ __('theme::shop.shipping_calculated_at_checkout') }}</p>

                    @if (! empty($freeShippingFrom))
                        @php($remaining = max(0, $freeShippingFrom - $summary->subtotal()))
                        <div class="mt-5">
                            <p class="text-sm text-zinc-700">
                                {{ $remaining > 0 ? __('theme::shop.free_shipping_remaining', ['amount' => format_price($remaining)]) : __('theme::shop.free_shipping_reached') }}
                            </p>
                            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-zinc-200">
                                <div class="h-full rounded-full bg-primary" style="width: {{ min(100, (int) round($summary->subtotal() / max(1, $freeShippingFrom) * 100)) }}%"></div>
                            </div>
                        </div>
                    @endif

                    @if (! empty($couponsEnabled))
                        <div class="mt-5 border-t border-zinc-200 pt-5">
                            @if (! empty($coupon))
                                <form method="POST" action="{{ route('coupon.remove') }}" class="flex items-center justify-between text-sm">
                                    @csrf @method('DELETE')
                                    <span>{{ __('theme::shop.coupon_applied', ['code' => $coupon->code]) }}</span>
                                    <button type="submit" class="text-zinc-600 underline underline-offset-2 hover:text-zinc-900">{{ __('theme::shop.remove_coupon') }}</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('coupon.apply') }}" class="flex gap-2">
                                    @csrf
                                    <label for="coupon-code" class="sr-only">{{ __('theme::shop.coupon') }}</label>
                                    <input id="coupon-code" name="code" placeholder="{{ __('theme::shop.coupon') }}" class="input py-1.5" autocomplete="off">
                                    <button type="submit" class="btn btn-secondary py-1.5">{{ __('theme::shop.apply') }}</button>
                                </form>
                            @endif
                        </div>
                    @endif

                    @if ($summary->canCheckout())
                        <a href="{{ route('checkout.show') }}" class="btn btn-primary mt-6 w-full">{{ __('theme::shop.checkout') }}</a>
                    @else
                        <p class="mt-6 text-sm text-red-600">{{ __('theme::shop.cart_has_unavailable') }}</p>
                    @endif
                    <a href="{{ route('storefront.home') }}" class="mt-3 block text-center text-sm text-zinc-600 hover:text-zinc-900">{{ __('theme::shop.continue_shopping') }}</a>
                </aside>
            </div>
        @endif
    </div>
@endsection
