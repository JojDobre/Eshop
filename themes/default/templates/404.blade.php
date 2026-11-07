@extends('theme::layouts.app')

@section('title', __('theme::shop.not_found'))
@section('noindex', true)

@section('content')
    <div class="container-shop py-24 text-center">
        <p class="text-sm font-semibold text-primary">404</p>
        <h1 class="mt-3 text-3xl font-semibold tracking-tight">{{ __('theme::shop.not_found') }}</h1>
        <p class="mt-4 text-zinc-600">{{ __('theme::shop.not_found_text') }}</p>
        <a href="{{ route('storefront.home') }}" class="btn btn-primary mt-8">{{ __('theme::shop.back_home') }}</a>
    </div>
@endsection
