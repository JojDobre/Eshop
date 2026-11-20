{{-- Prehľad objednávky: číslo, suma, stav, platba a priebeh krokov. --}}
@php
    /** @var \Modules\Orders\Models\Order $order */
    $order = $getRecord();
    $steps = \Modules\Orders\OrderFlow::steps($order);
    $current = array_search($order->status, $steps, true);
    $cancelled = $order->isCancelled();
@endphp
<div class="admin-order-summary">
    <div class="admin-order-summary-top">
        <div class="admin-order-summary-main">
            <div class="admin-order-summary-badges">
                <x-filament::badge :color="$order->status->getColor()">{{ $order->status->getLabel() }}</x-filament::badge>
                <x-filament::badge :color="$order->payment_status->getColor()" :icon="$order->isPaid() ? 'heroicon-m-check-circle' : 'heroicon-m-clock'">{{ $order->payment_status->getLabel() }}</x-filament::badge>
                @if ($order->payment_reminded_at && ! $order->isPaid())
                    <x-filament::badge color="gray" icon="heroicon-m-bell">{{ __('orders::admin.unpaid.reminded') }}</x-filament::badge>
                @endif
            </div>
            <p class="admin-order-summary-customer">{{ $order->billing_name }} <span>· {{ $order->email }}</span></p>
            <p class="admin-order-summary-meta">
                {{ $order->created_at->format(app()->getLocale() === 'en' ? 'j M Y H:i' : 'j. n. Y H:i') }}
                · {{ $order->shipping_method_name }} · {{ $order->payment_method_name }}
            </p>
        </div>
        <div class="admin-order-summary-total">
            <span>{{ __('orders::admin.total') }}</span>
            <strong>{{ \App\Core\Support\Price::format($order->total, $order->currency) }}</strong>
        </div>
    </div>

    @if ($cancelled)
        <p class="admin-order-summary-cancelled">{{ __('orders::admin.flow.cancelled') }}</p>
    @else
        <ol class="admin-steps" aria-label="{{ __('orders::admin.flow.label') }}">
            @foreach ($steps as $index => $step)
                <li @class(['is-done' => $current !== false && $index < $current, 'is-current' => $index === $current])>
                    <span class="admin-steps-dot">
                        @if ($current !== false && $index < $current)
                            <x-filament::icon icon="heroicon-m-check" class="size-3.5" />
                        @else
                            {{ $index + 1 }}
                        @endif
                    </span>
                    <span class="admin-steps-label">{{ $step->getLabel() }}</span>
                </li>
            @endforeach
        </ol>
    @endif
</div>
