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

        @if ($order->payment_type === \Modules\Payments\PaymentType::BankTransfer && ! $order->isPaid() && ! $order->isCancelled())
            <section class="mt-8 rounded-2xl border border-zinc-200 p-6">
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
            </section>
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
                <div class="flex justify-between"><dt>{{ $order->shipping_method_name }}</dt><dd>{{ $money($order->shipping_price) }}</dd></div>
                @if ($order->payment_fee > 0)
                    <div class="flex justify-between"><dt>{{ $order->payment_method_name }}</dt><dd>{{ $money($order->payment_fee) }}</dd></div>
                @endif
                <div class="flex justify-between border-t border-zinc-200 pt-3 text-base font-semibold"><dt>{{ __('theme::shop.total') }}</dt><dd>{{ $money($order->total) }}</dd></div>
            </dl>
        </section>

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

        <a href="{{ route('storefront.home') }}" class="btn btn-secondary mt-12">{{ __('theme::shop.continue_shopping') }}</a>
    </div>
@endsection
