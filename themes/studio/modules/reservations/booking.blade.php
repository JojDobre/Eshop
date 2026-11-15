@extends('theme::layouts.app')

{{-- Potvrdenie rezervácie v štýle Štúdia: veľké poďakovanie, karta termínu, platba, kalendár a zrušenie. --}}
@section('title', __('reservations::shop.booking_title', ['number' => $booking->number]))
@section('noindex', true)

@section('content')
    @php
        $bookedService = $booking->service;
        $bookedImage = $bookedService?->imageUrl();
        $cancelled = $booking->status === 'cancelled';
        $address = trim((string) app(\App\Core\Themes\ThemeOptions::class)->get('contact_address'));
    @endphp
    <div class="container-shop max-w-3xl py-14 text-center sm:py-20">
        <span @class(['mx-auto grid size-20 place-items-center rounded-full text-white', 'bg-pine' => ! $cancelled, 'bg-[var(--studio-muted)]' => $cancelled]) aria-hidden="true">
            @if ($cancelled)
                <svg class="size-9" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18" /></svg>
            @else
                <svg class="size-9" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" /></svg>
            @endif
        </span>
        <p class="eyebrow mt-8">{{ __('theme::studio.booked_kicker', ['number' => $booking->number]) }}</p>
        <h1 class="mt-3 text-5xl leading-none sm:text-6xl">{{ $cancelled ? __('reservations::admin.statuses.cancelled') : __('theme::studio.booked_title') }}</h1>
        @unless ($cancelled)
            <p class="mt-5 text-lg text-muted">{{ __('theme::studio.booked_text', ['email' => $booking->email]) }}</p>
        @endunless

        <div class="mt-10 rounded-[2rem] bg-white p-6 text-left sm:p-8">
            <div class="flex items-center gap-5">
                @if ($bookedImage)<img src="{{ $bookedImage }}" alt="" class="size-20 shrink-0 rounded-2xl object-cover">@endif
                <div class="min-w-0">
                    <p class="font-serif text-3xl leading-tight">{{ $booking->title() }}</p>
                    <p class="text-muted">
                        @if ($booking->product_id)
                            {{ __('reservations::shop.reserved_until') }} {{ $booking->ends_at->locale(app()->getLocale())->translatedFormat('j. F Y H:i') }}
                        @else
                            {{ $booking->starts_at->locale(app()->getLocale())->translatedFormat('l j. F') }} · {{ $booking->starts_at->format('H:i') }} – {{ $booking->ends_at->format('H:i') }}
                        @endif
                    </p>
                </div>
            </div>
            <dl class="mt-6 grid gap-4 border-t border-line pt-6 text-sm sm:grid-cols-3">
                @if ($booking->resource)
                    <div><dt class="text-muted">{{ __('reservations::shop.with') }}</dt><dd class="mt-1 font-semibold">{{ $booking->resource->name }}</dd></div>
                @endif
                @if ($address !== '')
                    <div><dt class="text-muted">{{ __('theme::studio.where') }}</dt><dd class="mt-1 font-semibold">{{ $address }}</dd></div>
                @endif
                <div><dt class="text-muted">{{ __('reservations::shop.status') }}</dt><dd class="mt-1 font-semibold">{{ __('reservations::admin.statuses.'.$booking->status) }}</dd></div>
                @if ($booking->amount_due > 0)
                    <div><dt class="text-muted">{{ __('reservations::shop.to_pay') }}</dt><dd class="mt-1 font-semibold">{{ format_price($booking->amount_due, $booking->currency) }} @if ($booking->is_paid)({{ __('reservations::shop.paid') }})@endif</dd></div>
                @endif
            </dl>

            @if ($booking->amount_due > 0 && ! $booking->is_paid && ! $cancelled)
                @php($company = app(\App\Settings\CompanySettings::class))
                <div class="mt-6 rounded-2xl bg-ivory p-5 text-sm">
                    <p class="font-semibold">{{ __('reservations::shop.payment_info') }}</p>
                    <p class="mt-1">IBAN: <strong>{{ $company->iban }}</strong> · {{ __('reservations::shop.variable_symbol') }}: <strong>{{ preg_replace('/\D/', '', $booking->number) }}</strong></p>
                </div>
            @endif

            <div class="mt-7 flex flex-wrap items-center gap-3">
                @if (! $booking->product_id && ! $cancelled)
                    <a href="{{ route('reservations.ics', $booking->token) }}" class="btn btn-pine">{{ __('theme::studio.calendar') }}</a>
                @endif
                @if ($address !== '' && ! $cancelled)
                    <a href="https://www.google.com/maps/search/?api=1&query={{ rawurlencode($address) }}" target="_blank" rel="noopener" class="btn btn-ghost">{{ __('theme::studio.navigate') }}</a>
                @endif
                @if ($booking->canBeCancelled())
                    <form method="POST" action="{{ route('reservations.cancel', $booking->token) }}" class="sm:ml-auto" onsubmit="return confirm(@js(__('reservations::shop.cancel_confirm')))">
                        @csrf
                        <button type="submit" class="text-sm text-muted underline underline-offset-4 hover:text-ink">{{ __('reservations::shop.cancel') }}</button>
                    </form>
                @endif
            </div>
        </div>

        @if (Route::has('reservations.index'))
            <a href="{{ route('reservations.index') }}" class="btn btn-ghost mt-10">{{ __('theme::studio.services_all') }}</a>
        @endif
    </div>
@endsection
