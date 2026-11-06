@extends('installer.layout')

@section('content')
    <h1 class="text-2xl font-semibold tracking-tight">{{ __('installer.database.heading') }}</h1>
    <p class="mt-3 text-zinc-600">{{ __('installer.database.intro') }}</p>

    @error('connection')
        <div class="mt-6"><x-installer.alert>{{ $message }}</x-installer.alert></div>
    @enderror

    <form method="POST" action="{{ route('install.database.save') }}" class="mt-8 space-y-5">
        @csrf
        <div class="grid gap-5 sm:grid-cols-4">
            <x-installer.field name="host" :label="__('installer.database.host')" :value="$defaults['host']" :help="__('installer.database.host_help')" class="sm:col-span-3" />
            <x-installer.field name="port" type="number" :label="__('installer.database.port')" :value="$defaults['port']" />
        </div>
        <x-installer.field name="database" :label="__('installer.database.database')" :value="$defaults['database']" />
        <div class="grid gap-5 sm:grid-cols-2">
            <x-installer.field name="username" :label="__('installer.database.username')" :value="$defaults['username']" />
            <x-installer.field name="password" type="password" :label="__('installer.database.password')" :required="false" autocomplete="new-password" />
        </div>
        <x-installer.field name="prefix" :label="__('installer.database.prefix')" :value="$defaults['prefix']" :help="__('installer.database.prefix_help')" :required="false" />

        @if (session('confirm_existing'))
            <label class="flex items-start gap-3 text-sm">
                <input type="checkbox" name="confirm_existing" value="1" class="mt-0.5 size-4 rounded border-zinc-300">
                <span>{{ __('installer.database.confirm_existing') }}</span>
            </label>
        @endif

        <div class="flex items-center justify-between gap-3 pt-3">
            <x-installer.button variant="secondary" :href="route('install.requirements')">{{ __('installer.back') }}</x-installer.button>
            <x-installer.button>{{ __('installer.database.test') }}</x-installer.button>
        </div>
    </form>
@endsection
