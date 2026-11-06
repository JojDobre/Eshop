@extends('installer.layout')

@section('content')
    <h1 class="text-2xl font-semibold tracking-tight">{{ __('installer.welcome.heading') }}</h1>
    <p class="mt-3 text-zinc-600">{{ __('installer.welcome.intro') }}</p>
    <p class="mt-2 text-zinc-600">{{ __('installer.welcome.prepare') }}</p>

    <form method="POST" action="{{ route('install.locale') }}" class="mt-8 space-y-6">
        @csrf
        <x-installer.select name="locale" :label="__('installer.welcome.language')" :options="$locales" :value="app()->getLocale()" />

        <div class="flex justify-end">
            <x-installer.button>{{ __('installer.continue') }}</x-installer.button>
        </div>
    </form>
@endsection
