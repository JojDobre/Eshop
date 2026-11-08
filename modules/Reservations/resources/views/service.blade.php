@extends('theme::layouts.app')

@section('title', $service->name)

@section('content')
    <div class="container-shop max-w-3xl py-10">
        <a href="{{ route('reservations.index') }}" class="text-sm text-zinc-500 hover:text-zinc-900">← {{ __('reservations::shop.title') }}</a>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight">{{ $service->name }}</h1>
        <p class="mt-2 text-zinc-600">{{ __('reservations::shop.duration', ['minutes' => $service->duration_minutes]) }}
            @if ($service->price > 0) · {{ format_price($service->price, $service->currency) }}@endif
            @if ($service->amountDue() > 0) · {{ __('reservations::shop.pay_'.$service->payment_mode, ['amount' => format_price($service->amountDue(), $service->currency)]) }}@endif
        </p>

        @if ($days === [])
            <p class="mt-8 text-zinc-600">{{ __('reservations::shop.no_slots') }}</p>
        @else
            <h2 class="mt-8 font-semibold">{{ __('reservations::shop.choose_day') }}</h2>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($days as $day)
                    <a href="{{ route('reservations.service', ['service' => $service, 'date' => $day]) }}" @class(['rounded-lg border px-3 py-2 text-sm', 'border-primary bg-primary text-primary-foreground' => $day === $date, 'border-zinc-200 hover:border-zinc-400' => $day !== $date])>
                        {{ \Illuminate\Support\Carbon::parse($day)->locale(app()->getLocale())->translatedFormat('D j. n.') }}
                    </a>
                @endforeach
            </div>

            <h2 class="mt-8 font-semibold">{{ __('reservations::shop.choose_time') }}</h2>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($slots as $slot)
                    <a href="{{ route('reservations.service', ['service' => $service, 'date' => $date, 'time' => $slot]) }}#formular" @class(['rounded-lg border px-3 py-2 text-sm', 'border-primary bg-primary text-primary-foreground' => $slot === $time, 'border-zinc-200 hover:border-zinc-400' => $slot !== $time])>{{ $slot }}</a>
                @endforeach
            </div>

            @if ($time && in_array($time, $slots, true))
                <form id="formular" method="POST" action="{{ route('reservations.book', $service) }}" class="mt-10 space-y-4 rounded-2xl border border-zinc-200 p-5">
                    @csrf
                    <input type="hidden" name="date" value="{{ $date }}">
                    <input type="hidden" name="time" value="{{ $time }}">
                    <p class="font-medium">{{ __('reservations::shop.selected', ['date' => \Illuminate\Support\Carbon::parse($date.' '.$time)->format('j. n. Y H:i')]) }}</p>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label for="customer_name" class="label">{{ __('reservations::shop.name') }}</label><input id="customer_name" name="customer_name" value="{{ old('customer_name') }}" required class="input mt-1.5" autocomplete="name">@error('customer_name') <p class="field-error">{{ $message }}</p> @enderror</div>
                        <div><label for="email" class="label">{{ __('reservations::shop.email') }}</label><input id="email" type="email" name="email" value="{{ old('email') }}" required class="input mt-1.5" autocomplete="email">@error('email') <p class="field-error">{{ $message }}</p> @enderror</div>
                        <div><label for="phone" class="label">{{ __('reservations::shop.phone') }}</label><input id="phone" type="tel" name="phone" value="{{ old('phone') }}" class="input mt-1.5" autocomplete="tel"></div>
                    </div>
                    <div><label for="note" class="label">{{ __('reservations::shop.note') }}</label><textarea id="note" name="note" rows="2" class="input mt-1.5">{{ old('note') }}</textarea></div>
                    <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="terms" value="1" required class="mt-0.5 size-4 accent-[var(--brand-primary)]"> <span>{{ __('reservations::shop.terms', ['hours' => app(\Modules\Reservations\ReservationSettings::class)->cancel_hours]) }}</span></label>
                    @error('terms') <p class="field-error">{{ $message }}</p> @enderror
                    <button type="submit" class="btn btn-primary">{{ __('reservations::shop.book') }}</button>
                </form>
            @endif
        @endif
    </div>
@endsection
