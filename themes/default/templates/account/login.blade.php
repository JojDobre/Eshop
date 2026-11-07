@extends('theme::layouts.app')

@section('title', __('theme::account.login'))
@section('noindex', true)

@section('content')
    <div class="container-shop max-w-md py-12">
        <h1 class="text-3xl font-semibold tracking-tight">{{ __('theme::account.login') }}</h1>

        <form method="POST" action="{{ route('customer.login') }}" class="mt-8 space-y-4" novalidate>
            @csrf
            @include('theme::partials.field', ['name' => 'email', 'type' => 'email', 'label' => __('theme::shop.email'), 'autocomplete' => 'email'])
            @include('theme::partials.field', ['name' => 'password', 'type' => 'password', 'label' => __('theme::account.password'), 'autocomplete' => 'current-password', 'noOld' => true])
            <div class="flex items-center justify-between text-sm">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="remember" value="1" class="size-4 rounded border-zinc-300 accent-[var(--brand-primary)]">
                    {{ __('theme::account.remember') }}
                </label>
                <a href="{{ route('customer.password.request') }}" class="underline underline-offset-2">{{ __('theme::account.forgot') }}</a>
            </div>
            <button type="submit" class="btn btn-primary w-full py-3">{{ __('theme::account.login') }}</button>
        </form>

        <p class="mt-8 text-center text-sm text-zinc-600">
            {{ __('theme::account.no_account') }}
            <a href="{{ route('customer.register') }}" class="font-medium text-zinc-900 underline underline-offset-2">{{ __('theme::account.register') }}</a>
        </p>
    </div>
@endsection
