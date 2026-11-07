@extends('theme::layouts.app')

@section('title', __('theme::account.register'))
@section('noindex', true)

@section('content')
    <div class="container-shop max-w-md py-12">
        <h1 class="text-3xl font-semibold tracking-tight">{{ __('theme::account.register') }}</h1>
        <p class="mt-3 text-zinc-600">{{ __('theme::account.register_intro') }}</p>

        <form method="POST" action="{{ route('customer.register') }}" class="mt-8 space-y-4" novalidate>
            @csrf
            @include('theme::partials.field', ['name' => 'name', 'label' => __('theme::shop.full_name'), 'autocomplete' => 'name'])
            @include('theme::partials.field', ['name' => 'email', 'type' => 'email', 'label' => __('theme::shop.email'), 'autocomplete' => 'email'])
            @include('theme::partials.field', ['name' => 'password', 'type' => 'password', 'label' => __('theme::account.password'), 'autocomplete' => 'new-password', 'noOld' => true, 'help' => __('theme::account.password_help')])
            @include('theme::partials.field', ['name' => 'password_confirmation', 'type' => 'password', 'label' => __('theme::account.password_confirmation'), 'autocomplete' => 'new-password', 'noOld' => true])

            <div>
                <label class="flex items-start gap-3 text-sm">
                    <input type="checkbox" name="terms" value="1" @checked(old('terms')) class="mt-0.5 size-4 rounded border-zinc-300 accent-[var(--brand-primary)]" @error('terms') aria-invalid="true" @enderror>
                    <span>
                        @if ($termsUrl = $legalPages->url(\Modules\Storefront\Pages\LegalPages::TERMS))
                            {!! __('theme::shop.accept_terms_link', ['url' => e($termsUrl)]) !!}
                        @else
                            {{ __('theme::shop.accept_terms') }}
                        @endif
                    </span>
                </label>
                @error('terms') <p class="field-error">{{ $message }}</p> @enderror
                @if ($privacyUrl = $legalPages->url(\Modules\Storefront\Pages\LegalPages::PRIVACY))
                    <p class="mt-2 text-xs text-zinc-500">{!! __('theme::shop.privacy_notice', ['url' => e($privacyUrl)]) !!}</p>
                @endif
            </div>

            <button type="submit" class="btn btn-primary w-full py-3">{{ __('theme::account.create_account') }}</button>
        </form>

        <p class="mt-8 text-center text-sm text-zinc-600">
            {{ __('theme::account.have_account') }}
            <a href="{{ route('customer.login') }}" class="font-medium text-zinc-900 underline underline-offset-2">{{ __('theme::account.login') }}</a>
        </p>
    </div>
@endsection
