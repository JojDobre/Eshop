{{-- Služby a cenník: karty služieb s filtrom podľa kategórie (bez JavaScriptu sa zobrazia všetky). --}}
@php
    $serviceCount = (int) $themeOptions->get('services_count', 6);
    $homeServices = $bookingServices->take($serviceCount);
    $serviceCategories = $homeServices->pluck('category')->filter()->unique()->values();
@endphp
@if ($homeServices->isNotEmpty())
    <section class="{{ $wrap }} py-16 sm:py-20" aria-labelledby="home-services" x-data="{ category: '' }">
        <div @class(['flex flex-wrap items-end justify-between gap-6', 'flex-row-reverse text-right' => $reverse])>
            <div>
                <p class="eyebrow">{{ $opt('services_kicker') }}</p>
                <h2 id="home-services" class="studio-h2 mt-3">{{ $opt('services_title') }}</h2>
            </div>
            @if ($serviceCategories->count() > 1)
                <div class="flex flex-wrap gap-2" x-cloak>
                    <button type="button" class="studio-chip" :aria-pressed="(category === '').toString()" @click="category = ''">{{ __('theme::studio.all_categories') }}</button>
                    @foreach ($serviceCategories as $serviceCategory)
                        <button type="button" class="studio-chip" :aria-pressed="(category === @js($serviceCategory)).toString()" @click="category = @js($serviceCategory)">{{ $serviceCategory }}</button>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="mt-10 grid gap-5 md:grid-cols-2 [&>*]:min-w-0">
            @foreach ($homeServices as $service)
                <div x-show="category === '' || category === @js((string) $service->category)" x-transition.opacity>
                    @include('theme::partials.service-card', ['service' => $service])
                </div>
            @endforeach
        </div>
        @if (Route::has('reservations.index'))
            <div class="mt-8 text-center"><a href="{{ route('reservations.index') }}" class="btn btn-ghost">{{ __('theme::studio.services_all') }}</a></div>
        @endif
    </section>
@endif
