@php($flashAdded = session('cart_added'))
@if ($flashAdded || session('success') || session('error'))
    <div class="container-shop pt-6">
        @if ($flashAdded)
            <div role="status" class="flex flex-col gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 sm:flex-row sm:items-center sm:justify-between">
                <span>{{ __('theme::shop.added_to_cart', ['product' => $flashAdded]) }}</span>
                <a href="{{ route('cart.show') }}" class="font-medium underline underline-offset-2">{{ __('theme::shop.go_to_cart') }}</a>
            </div>
        @endif
        @if (session('success'))
            <div role="status" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div role="alert" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
        @endif
    </div>
@endif
