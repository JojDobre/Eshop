@extends('theme::layouts.app')

@section('title', __('reservations::shop.title'))

@section('content')
    <div class="container-shop max-w-3xl py-10">
        <h1 class="text-3xl font-semibold tracking-tight">{{ __('reservations::shop.title') }}</h1>
        <div class="mt-8 space-y-4">
            @forelse ($services as $service)
                <a href="{{ route('reservations.service', $service) }}" class="block rounded-xl border border-zinc-200 p-5 hover:border-zinc-400">
                    <span class="flex justify-between gap-4 font-medium">
                        <span>{{ $service->name }}</span>
                        <span>{{ $service->price > 0 ? format_price($service->price, $service->currency) : __('reservations::shop.free') }}</span>
                    </span>
                    <span class="mt-1 block text-sm text-zinc-500">{{ __('reservations::shop.duration', ['minutes' => $service->duration_minutes]) }}</span>
                    @if ($service->description)<span class="mt-2 block text-sm text-zinc-600">{{ $service->description }}</span>@endif
                </a>
            @empty
                <p class="text-zinc-600">{{ __('reservations::shop.no_services') }}</p>
            @endforelse
        </div>
    </div>
@endsection
