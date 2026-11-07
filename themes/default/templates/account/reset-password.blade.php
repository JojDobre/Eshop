@extends('theme::layouts.app')

@section('title', __('theme::account.new_password'))
@section('noindex', true)

@section('content')
    <div class="container-shop max-w-md py-12">
        <h1 class="text-3xl font-semibold tracking-tight">{{ __('theme::account.new_password') }}</h1>

        <form method="POST" action="{{ route('customer.password.update') }}" class="mt-8 space-y-4" novalidate>
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            @include('theme::partials.field', ['name' => 'email', 'type' => 'email', 'label' => __('theme::shop.email'), 'autocomplete' => 'email', 'value' => $email])
            @include('theme::partials.field', ['name' => 'password', 'type' => 'password', 'label' => __('theme::account.password'), 'autocomplete' => 'new-password', 'noOld' => true, 'help' => __('theme::account.password_help')])
            @include('theme::partials.field', ['name' => 'password_confirmation', 'type' => 'password', 'label' => __('theme::account.password_confirmation'), 'autocomplete' => 'new-password', 'noOld' => true])
            <button type="submit" class="btn btn-primary w-full py-3">{{ __('theme::account.save_password') }}</button>
        </form>
    </div>
@endsection
