{{-- Rezervácia jedinečného kusu (napr. zviera) na stránke produktu. --}}
@if ($productReservation['reserved'])
    <p class="mt-6 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ __('reservations::shop.product_reserved') }}</p>
@else
    <details class="mt-6 rounded-xl border border-zinc-200 p-4">
        <summary class="cursor-pointer font-medium">{{ __('reservations::shop.reserve_product', ['days' => $productReservation['days']]) }}</summary>
        <form method="POST" action="{{ $productReservation['action'] }}" class="mt-4 grid gap-3 sm:grid-cols-2">
            @csrf
            <input name="customer_name" required placeholder="{{ __('reservations::shop.name') }}" class="input" autocomplete="name">
            <input type="email" name="email" required placeholder="{{ __('reservations::shop.email') }}" class="input" autocomplete="email">
            <input type="tel" name="phone" placeholder="{{ __('reservations::shop.phone') }}" class="input" autocomplete="tel">
            <button type="submit" class="btn btn-primary">{{ __('reservations::shop.reserve') }}</button>
            @if ($productReservation['deposit'])<p class="text-xs text-zinc-500 sm:col-span-2">{{ __('reservations::shop.product_deposit', ['amount' => $productReservation['deposit']]) }}</p>@endif
        </form>
    </details>
@endif
