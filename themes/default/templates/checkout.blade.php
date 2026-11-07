@extends('theme::layouts.app')

@section('title', __('theme::shop.checkout'))
@section('noindex', true)

@php
    $field = fn (string $name) => $errors->has($name) ? 'aria-invalid=true' : '';
    $firstShipping = old('shipping_method_id', $shippingMethods->first()?->id);
    $checkoutState = [
        'shipping' => (int) $firstShipping,
        'payment' => (int) old('payment_method_id', 0),
        'isCompany' => (bool) old('is_company'),
        'different' => (bool) old('ship_to_different_address'),
        'subtotal' => $summary->subtotal(),
        'availability' => $paymentAvailability,
        'shippingPrices' => $shippingPrices,
        'needsAddress' => $shippingMethods->mapWithKeys(fn ($method) => [$method->id => $method->type->requiresShippingAddress()]),
        'fees' => $paymentMethods->mapWithKeys(fn ($method) => [$method->id => $method->fee]),
        'locale' => str_replace('_', '-', app()->getLocale()),
        'currency' => $summary->currency,
    ];
@endphp

@section('content')
    <div class="container-shop py-10">
        <h1 class="text-3xl font-semibold tracking-tight">{{ __('theme::shop.checkout') }}</h1>

        @if ($errors->any())
            <div role="alert" class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ __('theme::shop.checkout_errors') }}</div>
        @endif

        <form method="POST" action="{{ route('checkout.store') }}" class="mt-8 grid gap-10 lg:grid-cols-3" novalidate
              x-data="checkout(@js($checkoutState))">
            @csrf

            <div class="space-y-10 lg:col-span-2">
                <fieldset class="space-y-4">
                    <legend class="text-lg font-semibold">{{ __('theme::shop.contact_details') }}</legend>
                    <div class="grid gap-4 sm:grid-cols-2">
                        @include('theme::partials.field', ['name' => 'email', 'type' => 'email', 'label' => __('theme::shop.email'), 'autocomplete' => 'email'])
                        @include('theme::partials.field', ['name' => 'phone', 'type' => 'tel', 'label' => __('theme::shop.phone'), 'autocomplete' => 'tel'])
                    </div>
                </fieldset>

                <fieldset class="space-y-4">
                    <legend class="text-lg font-semibold">{{ __('theme::shop.billing_details') }}</legend>
                    @include('theme::partials.field', ['name' => 'billing_name', 'label' => __('theme::shop.full_name'), 'autocomplete' => 'name'])
                    @include('theme::partials.field', ['name' => 'billing_street', 'label' => __('theme::shop.street'), 'autocomplete' => 'street-address'])
                    <div class="grid gap-4 sm:grid-cols-3">
                        @include('theme::partials.field', ['name' => 'billing_zip', 'label' => __('theme::shop.zip'), 'autocomplete' => 'postal-code'])
                        @include('theme::partials.field', ['name' => 'billing_city', 'label' => __('theme::shop.city'), 'autocomplete' => 'address-level2', 'class' => 'sm:col-span-2'])
                    </div>
                    @include('theme::partials.country-select', ['name' => 'billing_country'])

                    <label class="flex items-center gap-3 text-sm">
                        <input type="checkbox" name="is_company" value="1" x-model="isCompany" class="size-4 rounded border-zinc-300 accent-[var(--brand-primary)]">
                        {{ __('theme::shop.buying_as_company') }}
                    </label>
                    <div x-show="isCompany" x-cloak class="grid gap-4 sm:grid-cols-2">
                        @include('theme::partials.field', ['name' => 'billing_company', 'label' => __('theme::shop.company_name'), 'autocomplete' => 'organization', 'class' => 'sm:col-span-2'])
                        @include('theme::partials.field', ['name' => 'billing_company_id', 'label' => __('theme::shop.company_id')])
                        @include('theme::partials.field', ['name' => 'billing_tax_id', 'label' => __('theme::shop.tax_id'), 'required' => false])
                        @include('theme::partials.field', ['name' => 'billing_vat_id', 'label' => __('theme::shop.vat_id'), 'required' => false])
                    </div>
                </fieldset>

                <fieldset class="space-y-3">
                    <legend class="text-lg font-semibold">{{ __('theme::shop.shipping_method') }}</legend>
                    @foreach ($shippingMethods as $method)
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-zinc-200 p-4 has-[:checked]:border-primary has-[:checked]:ring-1 has-[:checked]:ring-primary">
                            <input type="radio" name="shipping_method_id" value="{{ $method->id }}" x-model.number="shipping" class="mt-1 accent-[var(--brand-primary)]">
                            <span class="flex-1">
                                <span class="flex justify-between gap-4 font-medium">
                                    <span>{{ $method->name }}</span>
                                    <span>{{ $shippingPrices[$method->id] > 0 ? format_price($shippingPrices[$method->id]) : __('theme::shop.free') }}</span>
                                </span>
                                @if ($method->description)
                                    <span class="mt-0.5 block text-sm text-zinc-500">{{ $method->description }}</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                    @error('shipping_method_id') <p class="field-error">{{ $message }}</p> @enderror
                </fieldset>

                <div x-show="needsAddress[shipping]" x-cloak class="space-y-4">
                    <label class="flex items-center gap-3 text-sm">
                        <input type="checkbox" name="ship_to_different_address" value="1" x-model="different" class="size-4 rounded border-zinc-300 accent-[var(--brand-primary)]">
                        {{ __('theme::shop.ship_to_different_address') }}
                    </label>
                    <fieldset x-show="different" x-cloak class="space-y-4">
                        <legend class="sr-only">{{ __('theme::shop.shipping_address') }}</legend>
                        @include('theme::partials.field', ['name' => 'shipping_name', 'label' => __('theme::shop.recipient_name'), 'autocomplete' => 'shipping name'])
                        @include('theme::partials.field', ['name' => 'shipping_street', 'label' => __('theme::shop.street'), 'autocomplete' => 'shipping street-address'])
                        <div class="grid gap-4 sm:grid-cols-3">
                            @include('theme::partials.field', ['name' => 'shipping_zip', 'label' => __('theme::shop.zip'), 'autocomplete' => 'shipping postal-code'])
                            @include('theme::partials.field', ['name' => 'shipping_city', 'label' => __('theme::shop.city'), 'autocomplete' => 'shipping address-level2', 'class' => 'sm:col-span-2'])
                        </div>
                        @include('theme::partials.country-select', ['name' => 'shipping_country'])
                    </fieldset>
                </div>

                <fieldset class="space-y-3">
                    <legend class="text-lg font-semibold">{{ __('theme::shop.payment_method') }}</legend>
                    @foreach ($paymentMethods as $method)
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-zinc-200 p-4 has-[:checked]:border-primary has-[:checked]:ring-1 has-[:checked]:ring-primary"
                               x-show="isPaymentAllowed({{ $method->id }})">
                            <input type="radio" name="payment_method_id" value="{{ $method->id }}" x-model.number="payment" class="mt-1 accent-[var(--brand-primary)]">
                            <span class="flex-1">
                                <span class="flex justify-between gap-4 font-medium">
                                    <span>{{ $method->name }}</span>
                                    <span>{{ $method->fee > 0 ? format_price($method->fee) : __('theme::shop.free') }}</span>
                                </span>
                                @if ($method->description)
                                    <span class="mt-0.5 block text-sm text-zinc-500">{{ $method->description }}</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                    @error('payment_method_id') <p class="field-error">{{ $message }}</p> @enderror
                </fieldset>

                <div>
                    <label for="customer_note" class="label">{{ __('theme::shop.note') }} <span class="font-normal text-zinc-500">({{ __('theme::shop.optional') }})</span></label>
                    <textarea id="customer_note" name="customer_note" rows="3" maxlength="1000" class="input mt-1.5">{{ old('customer_note') }}</textarea>
                </div>
            </div>

            <aside class="h-fit space-y-5 rounded-2xl bg-zinc-50 p-6 lg:sticky lg:top-24">
                <h2 class="text-lg font-semibold">{{ __('theme::shop.order_summary') }}</h2>
                <ul class="space-y-3 text-sm">
                    @foreach ($summary->lines as $line)
                        <li class="flex justify-between gap-4">
                            <span>{{ $line->quantity }} × {{ $line->product->name }}</span>
                            <span class="shrink-0">{{ format_price($line->total()) }}</span>
                        </li>
                    @endforeach
                </ul>
                <dl class="space-y-2 border-t border-zinc-200 pt-4 text-sm">
                    <div class="flex justify-between"><dt>{{ __('theme::shop.subtotal') }}</dt><dd>{{ format_price($summary->subtotal()) }}</dd></div>
                    <div class="flex justify-between"><dt>{{ __('theme::shop.shipping') }}</dt><dd x-text="money(shippingPrices[shipping] ?? 0)">—</dd></div>
                    <div class="flex justify-between" x-show="(fees[payment] ?? 0) > 0" x-cloak><dt>{{ __('theme::shop.payment_fee') }}</dt><dd x-text="money(fees[payment] ?? 0)"></dd></div>
                    <div class="flex justify-between border-t border-zinc-200 pt-3 text-base font-semibold">
                        <dt>{{ __('theme::shop.total') }}</dt>
                        <dd x-text="money(total())">{{ format_price($totals->total()) }}</dd>
                    </div>
                </dl>
                <p class="text-xs text-zinc-500">{{ $vatPayer ? __('theme::shop.price_includes_vat') : __('theme::shop.not_vat_payer') }}</p>

                <div>
                    <label class="flex items-start gap-3 text-sm">
                        <input type="checkbox" name="terms" value="1" @checked(old('terms')) required class="mt-0.5 size-4 rounded border-zinc-300 accent-[var(--brand-primary)]" @error('terms') aria-invalid="true" @enderror>
                        <span>
                            @if ($termsUrl = $legalPages->url(\Modules\Storefront\Pages\LegalPages::TERMS))
                                {!! __('theme::shop.accept_terms_link', ['url' => e($termsUrl)]) !!}
                            @else
                                {{ __('theme::shop.accept_terms') }}
                            @endif
                        </span>
                    </label>
                    @error('terms') <p class="field-error">{{ $message }}</p> @enderror
                    @if ($privacyUrl = $legalPages->url(\Modules\Storefront\Pages\LegalPages::PRIVACY))
                        <p class="mt-2 text-xs text-zinc-500">{!! __('theme::shop.privacy_notice', ['url' => e($privacyUrl)]) !!}</p>
                    @endif
                </div>

                <button type="submit" class="btn btn-primary w-full py-3">{{ __('theme::shop.place_order') }}</button>
            </aside>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            window.Alpine.data('checkout', (state) => ({
                ...state,
                init() {
                    this.$watch('shipping', () => this.ensurePayment());
                    this.ensurePayment();
                },
                isPaymentAllowed(id) {
                    return (this.availability[this.shipping] || []).includes(id);
                },
                ensurePayment() {
                    if (! this.isPaymentAllowed(this.payment)) {
                        this.payment = (this.availability[this.shipping] || [])[0] || 0;
                    }
                },
                total() {
                    return this.subtotal + (this.shippingPrices[this.shipping] || 0) + (this.fees[this.payment] || 0);
                },
                money(minor) {
                    return new Intl.NumberFormat(this.locale, { style: 'currency', currency: this.currency }).format(minor / 100);
                },
            }));
        });
    </script>
@endpush
