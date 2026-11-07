@extends('theme::layouts.app')

@section('title', __('theme::account.forgot'))
@section('noindex', true)

@section('content')
    <div class="container-shop max-w-md py-12">
        <h1 class="text-3xl font-semibold tracking-tight">{{ __('theme::account.forgot') }}</h1>
        <p class="mt-3 text-zinc-600">{{ __('theme::account.forgot_intro') }}</p>

        <form method="POST" action="{{ route('customer.password.email') }}" class="mt-8 space-y-4" novalidate>
            @csrf
            @include('theme::partials.field', ['name' => 'email', 'type' => 'email', 'label' => __('theme::shop.email'), 'autocomplete' => 'email'])
            <button type="submit" class="btn btn-primary w-full py-3">{{ __('theme::account.send_link') }}</button>
        </form>

        <p class="mt-8 text-center text-sm"><a href="{{ route('customer.login') }}" class="underline underline-offset-2">{{ __('theme::account.back_to_login') }}</a></p>
    </div>
@endsection
