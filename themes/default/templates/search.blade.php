@extends('theme::layouts.app')

@section('title', $term !== '' ? __('theme::shop.search_results_for', ['term' => $term]) : __('theme::shop.search'))
@section('noindex', true)

@section('content')
    <div class="container-shop py-10">
        <h1 class="text-3xl font-semibold tracking-tight">{{ __('theme::shop.search') }}</h1>

        <form action="{{ route('catalog.search') }}" method="GET" role="search" class="mt-6 flex max-w-xl gap-3">
            <label for="search-q" class="sr-only">{{ __('theme::shop.search') }}</label>
            <input id="search-q" type="search" name="q" value="{{ $term }}" placeholder="{{ __('theme::shop.search_placeholder') }}" class="input" autofocus>
            <button type="submit" class="btn btn-primary">{{ __('theme::shop.search') }}</button>
        </form>

        @if ($products === null)
            @if ($term !== '')
                <p class="mt-8 text-zinc-500">{{ __('theme::shop.search_too_short') }}</p>
            @endif
        @else
            <div class="mt-10 flex flex-wrap items-center justify-between gap-4 border-b border-zinc-100 pb-4">
                <p class="text-sm text-zinc-500">{{ trans_choice('theme::shop.products_count', $products->total(), ['count' => $products->total()]) }}</p>
                @include('theme::partials.sort')
            </div>
            <div class="mt-8">
                @if ($products->isEmpty())
                    <p class="py-16 text-center text-zinc-500">{{ __('theme::shop.search_no_results', ['term' => $term]) }}</p>
                @else
                    @include('theme::partials.product-grid', ['products' => $products])
                    {{ $products->links('theme::partials.pagination') }}
                @endif
            </div>
        @endif
    </div>
@endsection
