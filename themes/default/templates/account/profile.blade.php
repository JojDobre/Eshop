@extends('theme::layouts.app')

@section('title', __('theme::account.profile'))
@section('noindex', true)

@php
    $billing = (array) $account->billing_address;
    $shippingAddress = (array) $account->shipping_address;
@endphp

@section('content')
    <div class="container-shop max-w-4xl py-10">
        <h1 class="text-3xl font-semibold tracking-tight">{{ __('theme::account.profile') }}</h1>
        <div class="mt-6">@include('theme::partials.account-nav')</div>

        <form method="POST" action="{{ route('customer.profile.update') }}" class="mt-8 space-y-10" novalidate>
            @csrf
            @method('PUT')

            <fieldset class="space-y-4">
                <legend class="text-lg font-semibold">{{ __('theme::shop.contact_details') }}</legend>
                <div class="grid gap-4 sm:grid-cols-3">
                    @include('theme::partials.field', ['name' => 'name', 'label' => __('theme::shop.full_name'), 'autocomplete' => 'name', 'value' => $account->name])
                    @include('theme::partials.field', ['name' => 'email', 'type' => 'email', 'label' => __('theme::shop.email'), 'autocomplete' => 'email', 'value' => $account->email])
                    @include('theme::partials.field', ['name' => 'phone', 'type' => 'tel', 'label' => __('theme::shop.phone'), 'autocomplete' => 'tel', 'value' => $account->phone, 'required' => false])
                </div>
            </fieldset>

            <fieldset class="space-y-4">
                <legend class="text-lg font-semibold">{{ __('theme::shop.billing_details') }}</legend>
                <p class="text-sm text-zinc-500">{{ __('theme::account.address_help') }}</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    @include('theme::partials.field', ['name' => 'billing_address[name]', 'label' => __('theme::shop.full_name'), 'value' => $billing['name'] ?? null, 'required' => false])
                    @include('theme::partials.field', ['name' => 'billing_address[company]', 'label' => __('theme::shop.company_name'), 'value' => $billing['company'] ?? null, 'required' => false])
                    @include('theme::partials.field', ['name' => 'billing_address[street]', 'label' => __('theme::shop.street'), 'value' => $billing['street'] ?? null, 'required' => false, 'class' => 'sm:col-span-2'])
                    @include('theme::partials.field', ['name' => 'billing_address[zip]', 'label' => __('theme::shop.zip'), 'value' => $billing['zip'] ?? null, 'required' => false])
                    @include('theme::partials.field', ['name' => 'billing_address[city]', 'label' => __('theme::shop.city'), 'value' => $billing['city'] ?? null, 'required' => false])
                    @include('theme::partials.field', ['name' => 'billing_address[company_id]', 'label' => __('theme::shop.company_id'), 'value' => $billing['company_id'] ?? null, 'required' => false])
                    @include('theme::partials.field', ['name' => 'billing_address[tax_id]', 'label' => __('theme::shop.tax_id'), 'value' => $billing['tax_id'] ?? null, 'required' => false])
                    @include('theme::partials.field', ['name' => 'billing_address[vat_id]', 'label' => __('theme::shop.vat_id'), 'value' => $billing['vat_id'] ?? null, 'required' => false])
                    @include('theme::partials.country-select', ['name' => 'billing_address[country]', 'value' => $billing['country'] ?? null])
                </div>
            </fieldset>

            <fieldset class="space-y-4">
                <legend class="text-lg font-semibold">{{ __('theme::shop.shipping_address') }}</legend>
                <div class="grid gap-4 sm:grid-cols-2">
                    @include('theme::partials.field', ['name' => 'shipping_address[name]', 'label' => __('theme::shop.recipient_name'), 'value' => $shippingAddress['name'] ?? null, 'required' => false])
                    @include('theme::partials.field', ['name' => 'shipping_address[street]', 'label' => __('theme::shop.street'), 'value' => $shippingAddress['street'] ?? null, 'required' => false])
                    @include('theme::partials.field', ['name' => 'shipping_address[zip]', 'label' => __('theme::shop.zip'), 'value' => $shippingAddress['zip'] ?? null, 'required' => false])
                    @include('theme::partials.field', ['name' => 'shipping_address[city]', 'label' => __('theme::shop.city'), 'value' => $shippingAddress['city'] ?? null, 'required' => false])
                    @include('theme::partials.country-select', ['name' => 'shipping_address[country]', 'value' => $shippingAddress['country'] ?? null])
                </div>
            </fieldset>

            <button type="submit" class="btn btn-primary px-8 py-3">{{ __('theme::account.save') }}</button>
        </form>

        <form method="POST" action="{{ route('customer.profile.password') }}" class="mt-14 space-y-4 border-t border-zinc-100 pt-10" novalidate>
            @csrf
            @method('PUT')
            <h2 class="text-lg font-semibold">{{ __('theme::account.change_password') }}</h2>
            <div class="grid gap-4 sm:grid-cols-3">
                @include('theme::partials.field', ['name' => 'current_password', 'type' => 'password', 'label' => __('theme::account.current_password'), 'autocomplete' => 'current-password', 'noOld' => true, 'bag' => 'password'])
                @include('theme::partials.field', ['name' => 'password', 'type' => 'password', 'label' => __('theme::account.new_password'), 'autocomplete' => 'new-password', 'noOld' => true, 'bag' => 'password', 'help' => __('theme::account.password_help')])
                @include('theme::partials.field', ['name' => 'password_confirmation', 'type' => 'password', 'label' => __('theme::account.password_confirmation'), 'autocomplete' => 'new-password', 'noOld' => true, 'bag' => 'password'])
            </div>
            <button type="submit" class="btn btn-secondary">{{ __('theme::account.save_password') }}</button>
        </form>

        <form method="POST" action="{{ route('customer.destroy') }}" class="mt-14 space-y-4 border-t border-zinc-100 pt-10" novalidate
              x-data @submit="if (! confirm(@js(__('theme::account.delete_confirm')))) $event.preventDefault()">
            @csrf
            @method('DELETE')
            <h2 class="text-lg font-semibold">{{ __('theme::account.delete') }}</h2>
            <p class="text-sm text-zinc-600">{{ __('theme::account.delete_help') }}</p>
            <div class="max-w-sm">
                @include('theme::partials.field', ['name' => 'delete_password', 'type' => 'password', 'label' => __('theme::account.current_password'), 'autocomplete' => 'current-password', 'noOld' => true, 'bag' => 'delete'])
            </div>
            <button type="submit" class="btn btn-secondary text-red-700">{{ __('theme::account.delete') }}</button>
        </form>
    </div>
@endsection
