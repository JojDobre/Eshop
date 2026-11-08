@extends('theme::layouts.app')

@section('title', __('reservations::shop.booking_title', ['number' => $booking->number]))
@section('noindex', true)

@section('content')
    <div class="container-shop max-w-2xl py-10">
        <h1 class="text-3xl font-semibold tracking-tight">{{ __('reservations::shop.booking_title', ['number' => $booking->number]) }}</h1>
        <dl class="mt-6 space-y-2 rounded-2xl border border-zinc-200 p-5 text-sm">
            <div class="flex justify-between gap-4"><dt class="text-zinc-500">{{ __('reservations::shop.what') }}</dt><dd class="font-medium">{{ $booking->title() }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-zinc-500">{{ $booking->product_id ? __('reservations::shop.reserved_until') : __('reservations::shop.when') }}</dt>
                <dd>{{ ($booking->product_id ? $booking->ends_at : $booking->starts_at)->format('j. n. Y H:i') }}</dd></div>
            @if ($booking->resource)<div class="flex justify-between gap-4"><dt class="text-zinc-500">{{ __('reservations::shop.with') }}</dt><dd>{{ $booking->resource->name }}</dd></div>@endif
            <div class="flex justify-between gap-4"><dt class="text-zinc-500">{{ __('reservations::shop.status') }}</dt><dd>{{ __('reservations::admin.statuses.'.$booking->status) }}</dd></div>
            @if ($booking->amount_due > 0)
                <div class="flex justify-between gap-4"><dt class="text-zinc-500">{{ __('reservations::shop.to_pay') }}</dt><dd class="font-medium">{{ format_price($booking->amount_due, $booking->currency) }} @if ($booking->is_paid)({{ __('reservations::shop.paid') }})@endif</dd></div>
            @endif
        </dl>

        @if ($booking->amount_due > 0 && ! $booking->is_paid && $booking->status !== 'cancelled')
            @php($company = app(\App\Settings\CompanySettings::class))
            <div class="mt-6 rounded-2xl bg-zinc-50 p-5 text-sm">
                <p class="font-medium">{{ __('reservations::shop.payment_info') }}</p>
                <p class="mt-1">IBAN: <strong>{{ $company->iban }}</strong> · {{ __('reservations::shop.variable_symbol') }}: <strong>{{ preg_replace('/\D/', '', $booking->number) }}</strong></p>
            </div>
        @endif

        <div class="mt-6 flex flex-wrap gap-3">
            @if (! $booking->product_id)
                <a href="{{ route('reservations.ics', $booking->token) }}" class="btn btn-secondary">{{ __('reservations::shop.add_to_calendar') }}</a>
            @endif
            @if ($booking->canBeCancelled())
                <form method="POST" action="{{ route('reservations.cancel', $booking->token) }}" onsubmit="return confirm(@js(__('reservations::shop.cancel_confirm')))">
                    @csrf
                    <button type="submit" class="btn btn-secondary">{{ __('reservations::shop.cancel') }}</button>
                </form>
            @endif
        </div>
    </div>
@endsection
