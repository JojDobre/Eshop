@extends('installer.layout')

@section('content')
    <h1 class="text-2xl font-semibold tracking-tight">{{ __('installer.shop.heading') }}</h1>
    <p class="mt-3 text-zinc-600">{{ __('installer.shop.intro') }}</p>

    <div class="mt-6 space-y-3">
        @if (! empty($database['version']))
            <x-installer.alert type="success">{{ __('installer.database.connected', ['version' => $database['version']]) }}</x-installer.alert>
        @endif
        @if (! empty($database['limited']))
            <x-installer.alert type="warning">{{ __('installer.database.limited_support') }}</x-installer.alert>
        @endif
        @error('install')
            <x-installer.alert>{{ $message }}</x-installer.alert>
        @enderror
    </div>

    <form method="POST" action="{{ route('install.run') }}" class="mt-8 space-y-10"
          onsubmit="this.querySelector('[type=submit]').disabled = true; this.querySelector('[data-label]').textContent = this.querySelector('[data-label]').dataset.label;">
        @csrf

        <fieldset class="space-y-5">
            <legend class="text-sm font-semibold text-zinc-900">{{ __('installer.shop.section_shop') }}</legend>
            <x-installer.field name="shop_name" :label="__('installer.shop.shop_name')" />
            <div class="grid gap-5 sm:grid-cols-2">
                <x-installer.field name="contact_email" type="email" :label="__('installer.shop.contact_email')" autocomplete="email" />
                <x-installer.field name="url" type="url" :label="__('installer.shop.url')" :value="$defaults['url']" />
            </div>
            <div class="grid gap-5 sm:grid-cols-3">
                <x-installer.select name="locale" :label="__('installer.shop.locale')" :options="$locales" :value="$defaults['locale']" />
                <x-installer.select name="currency" :label="__('installer.shop.currency')"
                    :options="collect($currencies)->mapWithKeys(fn ($symbol, $code) => [$code => $code.' ('.$symbol.')'])" :value="$defaults['currency']" />
                <x-installer.select name="country" :label="__('installer.shop.country')"
                    :options="collect($countries)->mapWithKeys(fn ($code) => [$code => __('countries.'.$code)])" :value="$defaults['country']" />
            </div>
        </fieldset>

        <fieldset class="space-y-5">
            <legend class="text-sm font-semibold text-zinc-900">{{ __('installer.shop.section_admin') }}</legend>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-installer.field name="admin_name" :label="__('installer.shop.admin_name')" autocomplete="name" />
                <x-installer.field name="admin_email" type="email" :label="__('installer.shop.admin_email')" autocomplete="username" />
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-installer.field name="admin_password" type="password" :label="__('installer.shop.admin_password')" :help="__('installer.shop.admin_password_help')" autocomplete="new-password" />
                <x-installer.field name="admin_password_confirmation" type="password" :label="__('installer.shop.admin_password_confirmation')" autocomplete="new-password" />
            </div>
        </fieldset>

        <fieldset class="space-y-3">
            <legend class="text-sm font-semibold text-zinc-900">{{ __('admin.features.size.label') }}</legend>
            <p class="text-sm text-zinc-500">{{ __('installer.shop.size_help') }}</p>
            <div class="grid gap-3 sm:grid-cols-3">
                @foreach (\App\Core\Features\ShopSize::SIZES as $size)
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-zinc-200 p-4 text-sm has-[:checked]:border-zinc-900">
                        <input type="radio" name="size" value="{{ $size }}" @checked(old('size', 'small') === $size) class="mt-0.5 size-4">
                        <span>
                            <span class="font-medium text-zinc-900">{{ __('admin.features.size.sizes.'.$size) }}</span>
                            <span class="mt-1 block text-zinc-500">{{ __('admin.features.size.descriptions.'.$size) }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        <label class="flex items-start gap-3 rounded-lg border border-zinc-200 p-4 text-sm">
            <input type="checkbox" name="demo_data" value="1" @checked(old('demo_data')) class="mt-0.5 size-4 rounded border-zinc-300">
            <span>
                <span class="font-medium text-zinc-900">{{ __('installer.shop.demo_data') }}</span>
                <span class="mt-1 block text-zinc-500">{{ __('installer.shop.demo_data_help') }}</span>
            </span>
        </label>

        <div class="flex items-center justify-between gap-3">
            <x-installer.button variant="secondary" :href="route('install.database')">{{ __('installer.back') }}</x-installer.button>
            <x-installer.button>
                <span data-label="{{ __('installer.shop.installing') }}">{{ __('installer.shop.submit') }}</span>
            </x-installer.button>
        </div>
    </form>
@endsection
