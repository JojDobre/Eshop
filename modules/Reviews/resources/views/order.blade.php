@extends('theme::layouts.app')

@section('title', __('reviews::shop.order_title', ['number' => $order->number]))
@section('noindex', true)

@section('content')
    <div class="container-shop max-w-3xl py-10">
        <h1 class="text-3xl font-semibold tracking-tight">{{ __('reviews::shop.order_title', ['number' => $order->number]) }}</h1>
        <p class="mt-2 text-zinc-600">{{ __('reviews::shop.order_intro') }}</p>

        @if (session('review_status'))
            <p role="status" class="mt-6 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('review_status') }}</p>
        @endif

        <div class="mt-8 space-y-10">
            @foreach ($products as $product)
                <section>
                    <h2 class="text-lg font-semibold"><a href="{{ $product->url() }}" class="hover:underline">{{ $product->name }}</a></h2>
                    @if (in_array($product->id, $reviewed, true))
                        <p class="mt-2 text-sm text-emerald-700">{{ __('reviews::shop.already_reviewed') }}</p>
                    @else
                        <div class="mt-3">@include('reviews::form', ['action' => request()->fullUrl(), 'guest' => false, 'formId' => 'review-'.$product->id, 'productId' => $product->id])</div>
                    @endif
                </section>
            @endforeach
        </div>
    </div>
@endsection
