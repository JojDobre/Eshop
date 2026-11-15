{{-- Úvod: nadpis so zvýrazneným slovom, text, hodnotenie a fotka s rezervačným panelom (služba + deň). --}}
@php
    $heroTitle = preg_replace('/\*(.+?)\*/u', '<em>$1</em>', e($opt('hero_title')));
    $heroImage = $themeOptions->imageUrl('hero_image');
    $heroNote = trim((string) $themeOptions->get('hero_note'));
    $showPanel = $themeOptions->get('show_booking_panel', true) && $bookingServices->isNotEmpty();
    $panelDays = collect(range(0, 6))->map(fn ($i) => now()->startOfDay()->addDays($i))
        ->filter(fn ($day) => $day->isWeekday() || $day->isSaturday())->take(5)->values();
@endphp
<section class="relative overflow-hidden" aria-labelledby="home-hero">
    <div @class([$wrap, 'grid items-center gap-10 pt-10 sm:pt-16', 'lg:grid-cols-[1fr_1.05fr]' => $heroImage || $showPanel, 'pb-16 sm:pb-20' => $showPanel, 'pb-10' => ! $showPanel])>
        <div @class(['lg:order-2' => $reverse, 'mx-auto max-w-3xl text-center' => ! $heroImage && ! $showPanel])>
            <p class="eyebrow">{{ $opt('hero_kicker') }}</p>
            <h1 id="home-hero" class="mt-4 text-[3rem] leading-none tracking-tight sm:text-7xl lg:text-[5.4rem]">{!! $heroTitle !!}</h1>
            <p @class(['mt-6 max-w-md text-lg leading-relaxed text-muted', 'mx-auto' => ! $heroImage && ! $showPanel])>{{ $opt('hero_text') }}</p>
            <div class="mt-8 flex flex-wrap items-center gap-x-6 gap-y-3 text-sm">
                @if ($heroNote !== '')
                    <span class="flex items-center gap-2"><span class="text-gold" aria-hidden="true">★★★★★</span><span>{{ $heroNote }}</span></span>
                @endif
                <span class="text-muted">{{ $opt('hero_note_2') }}</span>
            </div>
            @if (! $showPanel && Route::has('reservations.index'))
                <a href="{{ route('reservations.index') }}" class="btn btn-pine mt-8">{{ __('theme::studio.book_button') }}</a>
            @endif
        </div>

        @if ($heroImage || $showPanel)
            <div @class(['relative', 'lg:order-1' => $reverse])>
                @if ($heroImage)
                    <img {{ responsive_img($heroImage, '(min-width: 1024px) 50vw, 95vw') }} alt="" class="aspect-[5/4] w-full rounded-[2.5rem] object-cover" fetchpriority="high">
                @endif

                @if ($showPanel)
                    <form method="GET" action="{{ route('reservations.service', $bookingServices->first()) }}"
                          x-data="{ action: @js(route('reservations.service', $bookingServices->first())) }" :action="action"
                          @class(['studio-card-shadow rounded-3xl bg-white p-5', 'relative -mt-24 mx-3 sm:absolute sm:-bottom-8 sm:mx-0 sm:mt-0 sm:w-[25rem]' => $heroImage, 'sm:-left-10' => $heroImage && ! $reverse, 'sm:-right-10' => $heroImage && $reverse])>
                        <p class="font-serif text-2xl">{{ __('theme::studio.panel_title') }}</p>
                        <div class="mt-4 grid gap-3">
                            <label class="block">
                                <span class="text-xs font-semibold text-muted">{{ __('theme::studio.panel_service') }}</span>
                                <select class="input mt-1 w-full text-[15px]" @change="action = $event.target.selectedOptions[0].dataset.url">
                                    @foreach ($bookingServices as $panelService)
                                        <option value="{{ $panelService->id }}" data-url="{{ route('reservations.service', $panelService) }}">{{ $panelService->name }} · {{ __('theme::studio.minutes', ['minutes' => $panelService->duration_minutes]) }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <fieldset>
                                <legend class="text-xs font-semibold text-muted">{{ __('theme::studio.panel_day') }}</legend>
                                <div class="mt-1 grid grid-cols-5 gap-1.5 text-center text-xs">
                                    @foreach ($panelDays as $panelDay)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="date" value="{{ $panelDay->toDateString() }}" class="peer sr-only" @checked($loop->first)>
                                            <span class="block rounded-xl border border-line py-2 transition peer-checked:border-[var(--brand-primary)] peer-checked:bg-pine peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-[var(--brand-primary)]">
                                                <b class="block text-sm">{{ \Illuminate\Support\Str::ucfirst($panelDay->locale(app()->getLocale())->minDayName) }}</b>{{ $panelDay->format('j.') }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                            <button type="submit" class="btn btn-pine mt-1 w-full">{{ __('theme::studio.panel_submit') }}</button>
                        </div>
                    </form>
                @endif
            </div>
        @endif
    </div>
</section>
