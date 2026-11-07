@extends('theme::layouts.app')

@section('title', __('theme::shop.order_number', ['number' => $order->number]))
@section('noindex', true)

@php($money = fn (int $amount) => format_price($amount, $order->currency))

@section('content')
    <div class="container-shop max-w-3xl py-10">
        @if ($justPlaced)
            <div class="flex size-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
            </div>
            <h1 class="mt-6 text-3xl font-semibold tracking-tight">{{ __('theme::shop.thank_you') }}</h1>
            <p class="mt-3 text-zinc-600">{{ __('theme::shop.order_received', ['number' => $order->number, 'email' => $order->email]) }}</p>
        @else
            <h1 class="text-3xl font-semibold tracking-tight">{{ __('theme::shop.order_number', ['number' => $order->number]) }}</h1>
            <p class="mt-3 text-zinc-600">{{ __('theme::shop.order_status') }}: <strong>{{ $order->status->getLabel() }}</strong></p>
        @endif

        @if (session('payment_error'))
            <p class="mt-6 rounded-xl bg-red-50 p-4 text-sm text-red-800" role="alert">{{ session('payment_error') }}</p>
        @elseif (session('payment_result') === 'paid')
            <p class="mt-6 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ __('theme::shop.payment_success') }}</p>
        @elseif (session('payment_result') === 'cancelled')
            <p class="mt-6 rounded-xl bg-amber-50 p-4 text-sm text-amber-900" role="status">{{ __('theme::shop.payment_cancelled') }}</p>
        @endif

        @if ($canPayOnline ?? false)
            <section class="mt-8 flex flex-col gap-4 rounded-2xl border border-zinc-200 p-6 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-zinc-700">{{ __('theme::shop.payment_waiting') }} <strong>{{ $money($order->total) }}</strong></p>
                <a href="{{ route('orders.pay', $order->token) }}" class="btn btn-primary px-6 py-3">{{ __('theme::shop.pay_online') }}</a>
            </section>
        @endif

        @if ($order->payment_type === \Modules\Payments\PaymentType::BankTransfer && ! $order->isPaid() && ! $order->isCancelled())
            <section class="mt-8 rounded-2xl border border-zinc-200 p-6">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex-1">
                <h2 class="font-semibold">{{ __('theme::shop.payment_details') }}</h2>
                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                    @if ($company->iban)
                        <div><dt class="text-zinc-500">{{ __('theme::shop.iban') }}</dt><dd class="font-mono font-medium">{{ $company->iban }}</dd></div>
                    @endif
                    @if ($company->swift)
                        <div><dt class="text-zinc-500">SWIFT / BIC</dt><dd class="font-mono font-medium">{{ $company->swift }}</dd></div>
                    @endif
                    <div><dt class="text-zinc-500">{{ __('theme::shop.variable_symbol') }}</dt><dd class="font-mono font-medium">{{ $order->variableSymbol() }}</dd></div>
                    <div><dt class="text-zinc-500">{{ __('theme::shop.amount') }}</dt><dd class="font-medium">{{ $money($order->total) }}</dd></div>
                </dl>
                </div>
                @if ($paymentQr ?? null)
                    <figure class="shrink-0 text-center">
                        <img src="{{ $paymentQr }}" alt="{{ __('theme::shop.pay_by_qr') }}" class="mx-auto size-40 rounded-lg border border-zinc-200 bg-white">
                        <figcaption class="mt-2 text-xs text-zinc-500">{{ __('theme::shop.pay_by_qr') }}</figcaption>
                    </figure>
                @endif
                </div>
            </section>
        @endif

        @if ($order->isReverseCharge())
            <p class="mt-6 rounded-xl bg-zinc-50 p-4 text-sm text-zinc-700">{{ __('theme::shop.reverse_charge_applied') }}</p>
        @endif

        @if ($order->payment_instructions)
            <p class="mt-6 rounded-xl bg-zinc-50 p-4 text-sm text-zinc-700">{{ $order->payment_instructions }}</p>
        @endif

        @if ($order->shipping_details)
            <section class="mt-6 rounded-xl bg-zinc-50 p-4 text-sm text-zinc-700">
                <h2 class="font-medium text-zinc-900">{{ $order->shipping_method_name }}</h2>
                <p class="mt-1">{!! nl2br(e($order->shipping_details)) !!}</p>
            </section>
        @endif

        <section class="mt-10">
            <h2 class="text-lg font-semibold">{{ __('theme::shop.order_summary') }}</h2>
            <ul class="mt-4 divide-y divide-zinc-100 border-y border-zinc-100 text-sm">
                @foreach ($order->items as $item)
                    <li class="flex justify-between gap-4 py-3">
                        <span>{{ $item->quantity }} × {{ $item->fullName() }}</span>
                        <span class="shrink-0">{{ $money($item->total) }}</span>
                    </li>
                @endforeach
            </ul>
            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between"><dt>{{ __('theme::shop.subtotal') }}</dt><dd>{{ $money($order->subtotal) }}</dd></div>
                @if ($order->discount > 0)
                    <div class="flex justify-between text-emerald-700"><dt>{{ __('theme::shop.discount_code', ['code' => $order->coupon_code]) }}</dt><dd>−{{ $money($order->discount) }}</dd></div>
                @endif
                <div class="flex justify-between"><dt>{{ $order->shipping_method_name }}</dt><dd>{{ $money($order->shipping_price) }}</dd></div>
                @if ($order->payment_fee > 0)
                    <div class="flex justify-between"><dt>{{ $order->payment_method_name }}</dt><dd>{{ $money($order->payment_fee) }}</dd></div>
                @endif
                <div class="flex justify-between border-t border-zinc-200 pt-3 text-base font-semibold"><dt>{{ __('theme::shop.total') }}</dt><dd>{{ $money($order->total) }}</dd></div>
            </dl>
        </section>

        @if (! empty($documents) && $documents->isNotEmpty())
            <section class="mt-10">
                <h2 class="text-lg font-semibold">{{ __('theme::shop.documents') }}</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($documents as $document)
                        <li>
                            <a href="{{ route('invoices.customer', [$order->token, $document->number]) }}" class="inline-flex items-center gap-2 underline underline-offset-2" target="_blank">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                                {{ $document->type->getLabel() }} {{ $document->number }} (PDF)
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <section class="mt-10 grid gap-6 text-sm sm:grid-cols-2">
            <div>
                <h2 class="font-semibold">{{ __('theme::shop.billing_details') }}</h2>
                <address class="mt-2 not-italic text-zinc-600">
                    {{ $order->billing_name }}<br>
                    @if ($order->billing_company) {{ $order->billing_company }}<br> @endif
                    {{ $order->billing_street }}<br>
                    {{ $order->billing_zip }} {{ $order->billing_city }}<br>
                    {{ __('countries.'.$order->billing_country) }}
                </address>
            </div>
            @if ($order->hasShippingAddress())
                <div>
                    <h2 class="font-semibold">{{ __('theme::shop.shipping_address') }}</h2>
                    <address class="mt-2 not-italic text-zinc-600">
                        {{ $order->shipping_name }}<br>
                        {{ $order->shipping_street }}<br>
                        {{ $order->shipping_zip }} {{ $order->shipping_city }}<br>
                        {{ __('countries.'.$order->shipping_country) }}
                    </address>
                </div>
            @endif
        </section>

        @if (! empty($orderFields))
            <section class="mt-10 text-sm">
                <h2 class="font-semibold">{{ __('theme::shop.additional_details') }}</h2>
                <dl class="mt-2 space-y-1 text-zinc-600">
                    @foreach ($orderFields as $label => $value)
                        <div><dt class="inline font-medium text-zinc-900">{{ $label }}:</dt> <dd class="inline">{{ $value }}</dd></div>
                    @endforeach
                </dl>
            </section>
        @endif

        @if (($returnRequests ?? collect())->isNotEmpty())
            <section class="mt-10 text-sm">
                <h2 class="font-semibold">{{ __('theme::shop.returns_requests') }}</h2>
                <ul class="mt-2 space-y-1 text-zinc-600">
                    @foreach ($returnRequests as $returnRequest)
                        <li>{{ $returnRequest->type->getLabel() }} {{ $returnRequest->number }} · <strong>{{ $returnRequest->status->getLabel() }}</strong>@if ($returnRequest->resolution) – {{ $returnRequest->resolution }}@endif</li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($canRequestReturn ?? false)
            <p class="mt-10 text-sm text-zinc-600">
                {{ __('theme::shop.returns_question') }}
                <a href="{{ route('returns.create', $order->token) }}" class="underline underline-offset-2">{{ __('theme::shop.returns_withdraw_link') }}</a>
                ·
                <a href="{{ route('returns.create', [$order->token, 'typ' => 'complaint']) }}" class="underline underline-offset-2">{{ __('theme::shop.returns_complaint_link') }}</a>
            </p>
        @endif

        @if (! empty($order->legal_consents))
            <section class="mt-10 text-sm">
                <h2 class="font-semibold">{{ __('theme::shop.accepted_documents') }}</h2>
                <ul class="mt-2 space-y-1 text-zinc-600">
                    @foreach ($order->legal_consents as $consent)
                        <li><a href="{{ $consent['url'] }}" class="underline underline-offset-2" target="_blank">{{ $consent['title'] }}</a> ({{ __('theme::shop.document_version_short', ['version' => $consent['version']]) }})</li>
                    @endforeach
                </ul>
            </section>
        @endif

        <a href="{{ route('storefront.home') }}" class="btn btn-secondary mt-12">{{ __('theme::shop.continue_shopping') }}</a>
    </div>
@endsection
