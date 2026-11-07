@extends('theme::layouts.app')

@section('title', __('theme::account.orders'))
@section('noindex', true)

@section('content')
    <div class="container-shop max-w-4xl py-10">
        <h1 class="text-3xl font-semibold tracking-tight">{{ __('theme::account.hello', ['name' => $customer->name]) }}</h1>
        <div class="mt-6">@include('theme::partials.account-nav')</div>

        @if ($orders->isEmpty())
            <p class="mt-10 text-zinc-600">{{ __('theme::account.no_orders') }}</p>
            <a href="{{ route('storefront.home') }}" class="btn btn-primary mt-6">{{ __('theme::shop.continue_shopping') }}</a>
        @else
            <div class="mt-8 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-zinc-200 text-zinc-500">
                        <tr>
                            <th scope="col" class="py-3 pr-4 font-medium">{{ __('theme::account.order_number') }}</th>
                            <th scope="col" class="py-3 pr-4 font-medium">{{ __('theme::account.date') }}</th>
                            <th scope="col" class="py-3 pr-4 font-medium">{{ __('theme::shop.order_status') }}</th>
                            <th scope="col" class="py-3 pr-4 text-right font-medium">{{ __('theme::shop.total') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @foreach ($orders as $order)
                            <tr>
                                <td class="py-3 pr-4"><a href="{{ $order->publicUrl() }}" class="font-medium underline underline-offset-2">{{ $order->number }}</a></td>
                                <td class="py-3 pr-4 text-zinc-600">{{ $order->created_at->translatedFormat('j. n. Y') }}</td>
                                <td class="py-3 pr-4">
                                    {{ $order->status->getLabel() }}
                                    @unless ($order->isPaid() || $order->isCancelled() || ! $order->payment_type->isPaidInAdvance())
                                        <span class="ml-1 text-amber-700">· {{ __('theme::account.awaiting_payment') }}</span>
                                    @endunless
                                </td>
                                <td class="py-3 pr-4 text-right">{{ format_price($order->total, $order->currency) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-6">{{ $orders->links('theme::partials.pagination') }}</div>
        @endif
    </div>
@endsection
