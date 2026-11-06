@extends('installer.layout')

@section('content')
    <h1 class="text-2xl font-semibold tracking-tight">{{ __('installer.requirements.heading') }}</h1>
    <p class="mt-3 text-zinc-600">{{ __('installer.requirements.intro') }}</p>

    <div class="mt-8 space-y-8">
        @foreach (['php', 'writable', 'limits'] as $group)
            @continue(! isset($groups[$group]))
            <div>
                <h2 class="text-sm font-semibold text-zinc-900">{{ __('installer.requirements.'.$group) }}</h2>
                <ul class="mt-3 divide-y divide-zinc-100 rounded-lg border border-zinc-200">
                    @foreach ($groups[$group] as $item)
                        <li class="flex items-center justify-between gap-4 px-4 py-2.5 text-sm">
                            <span class="font-mono text-zinc-700">{{ $item->label }}</span>
                            <span @class([
                                'shrink-0 text-xs font-medium',
                                'text-emerald-700' => $item->passed,
                                'text-red-700' => ! $item->passed && $item->required,
                                'text-amber-700' => ! $item->passed && ! $item->required,
                            ])>
                                {{ $item->current ? $item->current.' · ' : '' }}
                                @if ($item->passed)
                                    {{ __('installer.requirements.ok') }}
                                @elseif ($item->required)
                                    {{ __('installer.requirements.missing') }}
                                @else
                                    {{ __('installer.requirements.warning') }}
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>

    <div class="mt-8">
        @if ($passes)
            <x-installer.alert type="success">{{ __('installer.requirements.passed') }}</x-installer.alert>
        @else
            <x-installer.alert>{{ __('installer.requirements.failed') }}</x-installer.alert>
        @endif
    </div>

    <div class="mt-8 flex items-center justify-between gap-3">
        <x-installer.button variant="secondary" :href="route('install.welcome')">{{ __('installer.back') }}</x-installer.button>
        @if ($passes)
            <x-installer.button :href="route('install.database')">{{ __('installer.continue') }}</x-installer.button>
        @else
            <x-installer.button :href="route('install.requirements')">{{ __('installer.recheck') }}</x-installer.button>
        @endif
    </div>
@endsection
