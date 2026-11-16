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
            {{-- Mobil: objednávky ako kartičky (tabuľka by sa musela posúvať do strany). --}}
            <ul class="mt-8 space-y-3 sm:hidden">
                @foreach ($orders as $order)
                    <li class="rounded-2xl border border-zinc-200 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <a href="{{ $order->publicUrl() }}" class="font-semibold underline underline-offset-2">{{ $order->number }}</a>
                                <p class="text-sm text-zinc-600">{{ $order->created_at->translatedFormat('j. n. Y') }}</p>
                            </div>
                            <p class="whitespace-nowrap font-semibold">{{ format_price($order->total, $order->currency) }}</p>
                        </div>
                        <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                            <div>
                                @include('theme::partials.order-status', ['order' => $order])
                                @unless ($order->isPaid() || $order->isCancelled() || ! $order->payment_type->isPaidInAdvance())
                                    <span class="mt-1 block text-xs font-medium text-amber-700">{{ __('theme::account.awaiting_payment') }}</span>
                                @endunless
                            </div>
                            @if (Route::has('customer.orders.reorder') && Route::has('cart.show'))
                                <form method="POST" action="{{ route('customer.orders.reorder', $order) }}">
                                    @csrf
                                    <button type="submit" class="whitespace-nowrap rounded-full border border-zinc-200 px-3 py-1.5 text-xs font-semibold hover:border-zinc-400" aria-label="{{ __('theme::account.reorder_label', ['number' => $order->number]) }}">{{ __('theme::account.reorder') }}</button>
                                </form>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="mt-8 hidden overflow-x-auto sm:block">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-zinc-200 text-zinc-500">
                        <tr>
                            <th scope="col" class="py-3 pr-4 font-medium">{{ __('theme::account.order_number') }}</th>
                            <th scope="col" class="py-3 pr-4 font-medium">{{ __('theme::account.date') }}</th>
                            <th scope="col" class="py-3 pr-4 font-medium">{{ __('theme::shop.order_status') }}</th>
                            <th scope="col" class="py-3 pr-4 text-right font-medium">{{ __('theme::shop.total') }}</th>
                            @if (Route::has('customer.orders.reorder') && Route::has('cart.show'))
                                <th scope="col" class="py-3 text-right font-medium"><span class="sr-only">{{ __('theme::account.reorder') }}</span></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @foreach ($orders as $order)
                            <tr>
                                <td class="py-3 pr-4"><a href="{{ $order->publicUrl() }}" class="font-medium underline underline-offset-2">{{ $order->number }}</a></td>
                                <td class="py-3 pr-4 text-zinc-600">{{ $order->created_at->translatedFormat('j. n. Y') }}</td>
                                <td class="py-3 pr-4">
                                    @include('theme::partials.order-status', ['order' => $order])
                                    @unless ($order->isPaid() || $order->isCancelled() || ! $order->payment_type->isPaidInAdvance())
                                        <span class="mt-1 block text-xs font-medium text-amber-700">{{ __('theme::account.awaiting_payment') }}</span>
                                    @endunless
                                </td>
                                <td class="whitespace-nowrap py-3 pr-4 text-right">{{ format_price($order->total, $order->currency) }}</td>
                                @if (Route::has('customer.orders.reorder') && Route::has('cart.show'))
                                    <td class="py-3 text-right">
                                        <form method="POST" action="{{ route('customer.orders.reorder', $order) }}">
                                            @csrf
                                            <button type="submit" class="whitespace-nowrap rounded-full border border-zinc-200 px-3 py-1.5 text-xs font-semibold hover:border-zinc-400" aria-label="{{ __('theme::account.reorder_label', ['number' => $order->number]) }}">{{ __('theme::account.reorder') }}</button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-6">{{ $orders->links('theme::partials.pagination') }}</div>
        @endif
    </div>
@endsection
