@extends('installer.layout')

@section('content')
    <div class="flex size-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
        </svg>
    </div>

    <h1 class="mt-6 text-2xl font-semibold tracking-tight">{{ __('installer.done.heading') }}</h1>
    <p class="mt-3 text-zinc-600">{{ __('installer.done.intro', ['email' => $completed['admin_email']]) }}</p>

    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
        <x-installer.button :href="$completed['url'].'/admin'">{{ __('installer.done.admin') }}</x-installer.button>
        <x-installer.button variant="secondary" :href="$completed['url']">{{ __('installer.done.shop') }}</x-installer.button>
    </div>

    <div class="mt-10 border-t border-zinc-100 pt-8">
        <h2 class="text-sm font-semibold">{{ __('installer.done.cron_heading') }}</h2>
        <p class="mt-2 text-sm text-zinc-600">{{ __('installer.done.cron_intro') }}</p>
        <pre class="mt-3 overflow-x-auto rounded-lg bg-zinc-900 px-4 py-3 text-xs text-zinc-100"><code>{{ $cronCommand }}</code></pre>
    </div>
@endsection
