@extends('theme::layouts.app')

{{-- Služby a cenník v štýle Štúdia: služby zoskupené podľa kategórie, karty s fotkou, dĺžkou a cenou. --}}
@section('title', __('reservations::shop.title'))

@section('content')
    @php($groups = $services->groupBy(fn ($service) => (string) $service->category))
    <div class="container-shop py-10 sm:py-14">
        <p class="eyebrow">{{ __('theme::studio.services_kicker') }}</p>
        <h1 class="mt-3 text-5xl leading-none sm:text-6xl">{{ __('reservations::shop.title') }}</h1>
        <p class="mt-4 max-w-xl text-lg text-muted">{{ __('theme::studio.services_page_text') }}</p>

        @if ($groups->count() > 1)
            <nav class="mt-8 flex flex-wrap gap-2" aria-label="{{ __('theme::studio.services_kicker') }}">
                @foreach ($groups->keys()->filter() as $groupName)
                    <a href="#{{ \Illuminate\Support\Str::slug($groupName) }}" class="studio-chip">{{ $groupName }}</a>
                @endforeach
            </nav>
        @endif

        @forelse ($groups as $groupName => $groupServices)
            <section @if ($groupName !== '') id="{{ \Illuminate\Support\Str::slug($groupName) }}" @endif class="mt-12 scroll-mt-28">
                @if ($groupName !== '' && $groups->count() > 1)
                    <h2 class="mb-5 text-3xl sm:text-4xl">{{ $groupName }}</h2>
                @endif
                <div class="grid gap-5 md:grid-cols-2 [&>*]:min-w-0">
                    @foreach ($groupServices as $service)
                        @include('theme::partials.service-card', ['service' => $service])
                    @endforeach
                </div>
            </section>
        @empty
            <p class="mt-10 text-muted">{{ __('reservations::shop.no_services') }}</p>
        @endforelse
    </div>
@endsection
