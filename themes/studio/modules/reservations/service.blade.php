@extends('theme::layouts.app')

{{--
    Rezervácia služby v štýle Štúdia: 1. deň s počtom voľných termínov, 2. čas podľa časti dňa, 3. údaje,
    vedľa súhrn rezervácie. Funguje bez JavaScriptu (deň a čas sú v parametroch adresy).
--}}
@section('title', $service->name)

@section('content')
    @php
        $serviceImage = $service->imageUrl();
        $locale = app()->getLocale();
        $firstDay = now()->startOfDay();
        $lastDay = $days === [] ? $firstDay : \Illuminate\Support\Carbon::parse(end($days));
        $calendar = collect(range(0, max(6, min(13, (int) $firstDay->diffInDays($lastDay)))))->map(fn ($i) => $firstDay->copy()->addDays($i));
        $parts = collect($slots)->groupBy(fn ($slot) => (int) substr($slot, 0, 2) < 12 ? 'morning' : ((int) substr($slot, 0, 2) < 17 ? 'afternoon' : 'evening'));
        $hasTime = $time && in_array($time, $slots, true);
        $end = $hasTime ? \Illuminate\Support\Carbon::parse($date.' '.$time)->addMinutes($service->duration_minutes)->format('H:i') : null;
        $due = $service->amountDue();
    @endphp
    <div class="container-shop py-8 sm:py-12">
        <nav class="text-sm text-muted" aria-label="{{ __('theme::shop.breadcrumbs') }}">
            <a href="{{ route('storefront.home') }}" class="hover:text-ink">{{ __('theme::shop.home') }}</a>
            <span class="mx-1.5" aria-hidden="true">/</span>
            <a href="{{ route('reservations.index') }}" class="hover:text-ink">{{ __('reservations::shop.title') }}</a>
            <span class="mx-1.5" aria-hidden="true">/</span>
            <span class="text-ink" aria-current="page">{{ $service->name }}</span>
        </nav>

        <div class="mt-6 grid gap-10 lg:grid-cols-[1fr_380px] lg:gap-14">
            <div class="min-w-0">
                <div @class(['grid items-center gap-6', 'sm:grid-cols-[240px_1fr]' => $serviceImage])>
                    @if ($serviceImage)
                        <img {{ responsive_img($serviceImage, '(min-width: 640px) 240px, 90vw') }} alt="" class="aspect-[16/10] w-full rounded-[2rem] object-cover sm:aspect-square" fetchpriority="high">
                    @endif
                    <div>
                        @if (filled($service->category))<p class="eyebrow">{{ $service->category }}</p>@endif
                        <h1 class="mt-2 text-5xl leading-none sm:text-6xl">{{ $service->name }}</h1>
                        @if (filled($service->description))<p class="mt-4 leading-relaxed text-muted">{{ $service->description }}</p>@endif
                        <div class="mt-5 flex flex-wrap gap-2 text-sm font-semibold">
                            <span class="rounded-full bg-white px-4 py-2">{{ __('theme::studio.minutes', ['minutes' => $service->duration_minutes]) }}</span>
                            <span class="rounded-full bg-white px-4 py-2">{{ $service->price > 0 ? format_price($service->price, $service->currency) : __('theme::studio.free') }}</span>
                            @if ($due > 0)<span class="rounded-full bg-white px-4 py-2">{{ __('reservations::shop.pay_'.$service->payment_mode, ['amount' => format_price($due, $service->currency)]) }}</span>@endif
                        </div>
                    </div>
                </div>

                @if ($days === [])
                    <p class="mt-12 rounded-3xl bg-white p-6 text-muted">{{ __('reservations::shop.no_slots') }}</p>
                @else
                    <section class="mt-12" aria-labelledby="choose-day">
                        <h2 id="choose-day" class="text-3xl">{{ __('theme::studio.choose_day') }}</h2>
                        <p class="mt-1 text-sm text-muted">{{ \Illuminate\Support\Str::ucfirst($firstDay->locale($locale)->translatedFormat('F Y')) }}</p>
                        <div class="mt-4 grid grid-cols-4 gap-2 text-center sm:grid-cols-7">
                            @foreach ($calendar as $day)
                                @php($dayKey = $day->toDateString())
                                @php($count = $dayCounts[$dayKey] ?? 0)
                                @if ($count > 0)
                                    <a href="{{ route('reservations.service', ['service' => $service, 'date' => $dayKey]) }}#cas" @if ($dayKey === $date) aria-current="date" @endif
                                       @class(['rounded-2xl py-3 transition', 'bg-pine text-white' => $dayKey === $date, 'border border-line bg-white hover:border-[var(--studio-ink)]' => $dayKey !== $date])>
                                        <span @class(['block text-xs', 'opacity-80' => $dayKey === $date, 'text-muted' => $dayKey !== $date])>{{ \Illuminate\Support\Str::ucfirst($day->locale($locale)->minDayName) }}</span>
                                        <b class="block text-xl">{{ $day->format('j') }}</b>
                                        <span @class(['block text-[11px]', 'opacity-90' => $dayKey === $date, 'text-emerald-700' => $dayKey !== $date && $count > 2, 'text-amber-700' => $dayKey !== $date && $count <= 2])>{{ trans_choice('theme::studio.free_slots', $count, ['count' => $count]) }}</span>
                                    </a>
                                @else
                                    <span class="rounded-2xl border border-dashed border-line py-3 text-[color-mix(in_srgb,var(--studio-ink)_38%,var(--studio-bg))]">
                                        <span class="block text-xs">{{ \Illuminate\Support\Str::ucfirst($day->locale($locale)->minDayName) }}</span>
                                        <b class="block text-xl">{{ $day->format('j') }}</b>
                                        <span class="block text-[11px]">–</span>
                                    </span>
                                @endif
                            @endforeach
                        </div>
                    </section>

                    <section id="cas" class="mt-10 scroll-mt-28" aria-labelledby="choose-time">
                        <h2 id="choose-time" class="text-3xl">{{ __('theme::studio.choose_time') }}</h2>
                        <div class="mt-4 space-y-5">
                            @foreach (['morning', 'afternoon', 'evening'] as $part)
                                @if ($parts->has($part))
                                    <div>
                                        <p class="text-sm font-semibold text-muted">{{ __('theme::studio.part_'.$part) }}</p>
                                        <div class="mt-2 grid grid-cols-3 gap-2 sm:grid-cols-6">
                                            @foreach ($parts[$part] as $slot)
                                                <a href="{{ route('reservations.service', ['service' => $service, 'date' => $date, 'time' => $slot]) }}#formular" @class(['studio-slot', 'is-active' => $slot === $time]) @if ($slot === $time) aria-current="true" @endif>{{ $slot }}</a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </section>

                    <form id="formular" method="POST" action="{{ route('reservations.book', $service) }}" class="mt-10 scroll-mt-28 rounded-[2rem] bg-white p-6 sm:p-8">
                        @csrf
                        <input type="hidden" name="date" value="{{ $date }}">
                        <input type="hidden" name="time" value="{{ $hasTime ? $time : '' }}">
                        <h2 class="text-3xl">{{ __('theme::studio.your_details') }}</h2>
                        <div class="mt-5 grid gap-4 sm:grid-cols-2 [&>*]:min-w-0">
                            <div><label for="customer_name" class="label text-sm">{{ __('reservations::shop.name') }}</label><input id="customer_name" name="customer_name" value="{{ old('customer_name') }}" required class="input mt-1.5 w-full" autocomplete="name">@error('customer_name') <p class="field-error">{{ $message }}</p> @enderror</div>
                            <div><label for="phone" class="label text-sm">{{ __('reservations::shop.phone') }}</label><input id="phone" type="tel" name="phone" value="{{ old('phone') }}" class="input mt-1.5 w-full" autocomplete="tel"></div>
                            <div class="sm:col-span-2"><label for="email" class="label text-sm">{{ __('reservations::shop.email') }}</label><input id="email" type="email" name="email" value="{{ old('email') }}" required class="input mt-1.5 w-full" autocomplete="email">@error('email') <p class="field-error">{{ $message }}</p> @enderror</div>
                            <div class="sm:col-span-2"><label for="note" class="label text-sm">{{ __('reservations::shop.note') }}</label><textarea id="note" name="note" rows="2" class="input mt-1.5 w-full">{{ old('note') }}</textarea></div>
                        </div>
                        <label class="mt-4 flex items-start gap-3 text-sm"><input type="checkbox" name="terms" value="1" required class="mt-0.5 size-4 shrink-0 accent-[var(--brand-primary)]"> <span>{{ __('reservations::shop.terms', ['hours' => app(\Modules\Reservations\ReservationSettings::class)->cancel_hours]) }}</span></label>
                        @error('terms') <p class="field-error">{{ $message }}</p> @enderror
                        @error('time') <p class="field-error">{{ $message }}</p> @enderror
                        <button type="submit" class="btn btn-pine mt-6 w-full lg:hidden" @disabled(! $hasTime)>{{ __('reservations::shop.book') }}</button>
                    </form>
                @endif
            </div>

            <aside class="h-fit lg:sticky lg:top-28" aria-label="{{ __('theme::studio.summary') }}">
                <div class="studio-card-shadow rounded-[2rem] bg-white p-6">
                    <p class="font-serif text-2xl">{{ __('theme::studio.summary') }}</p>
                    <div class="mt-4 flex gap-4">
                        @if ($serviceImage)<img src="{{ $serviceImage }}" alt="" class="size-16 rounded-2xl object-cover">@endif
                        <div><p class="font-semibold">{{ $service->name }}</p><p class="text-sm text-muted">{{ __('theme::studio.minutes', ['minutes' => $service->duration_minutes]) }}</p></div>
                    </div>
                    <dl class="mt-5 space-y-3 border-t border-line pt-5 text-[15px]">
                        <div class="flex justify-between gap-4"><dt class="text-muted">{{ __('theme::studio.summary_date') }}</dt><dd class="text-right font-semibold">{{ $date ? \Illuminate\Support\Carbon::parse($date)->locale($locale)->translatedFormat('l j. n.') : '–' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-muted">{{ __('theme::studio.summary_time') }}</dt><dd class="font-semibold">{{ $hasTime ? $time.' – '.$end : '–' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-muted">{{ __('theme::studio.summary_price') }}</dt><dd class="font-semibold">{{ $service->price > 0 ? format_price($service->price, $service->currency) : __('theme::studio.free') }}</dd></div>
                        @if ($due > 0)
                            <div class="flex justify-between gap-4"><dt class="text-muted">{{ __('theme::studio.summary_payment') }}</dt><dd class="text-right">{{ __('reservations::shop.pay_'.$service->payment_mode, ['amount' => format_price($due, $service->currency)]) }}</dd></div>
                        @endif
                    </dl>
                    @if ($days !== [])
                        <button type="submit" form="formular" class="btn btn-pine mt-6 hidden w-full text-base disabled:cursor-not-allowed disabled:opacity-50 lg:flex" @disabled(! $hasTime)>{{ $hasTime ? __('reservations::shop.book') : __('theme::studio.summary_pick') }}</button>
                    @endif
                    <p class="mt-4 text-center text-xs text-muted">{{ __('theme::studio.summary_note') }}</p>
                </div>
            </aside>
        </div>
    </div>
@endsection
