@extends('theme::layouts.app')

@section('title', $title)
@section('noindex', true)

@section('content')
    <div class="container-shop max-w-2xl py-16 text-center">
        <h1 class="text-3xl font-semibold tracking-tight">{{ $title }}</h1>
        <p class="mt-4 text-zinc-600">{{ $message }}</p>
        <a href="{{ route('storefront.home') }}" class="btn btn-primary mt-8">{{ __('marketing::shop.back_to_shop') }}</a>
    </div>
@endsection
