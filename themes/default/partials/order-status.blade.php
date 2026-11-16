{{-- Farebný štítok stavu objednávky (napr. „Pripravená na vyzdvihnutie“). Farby sedia aj v tmavých paletách šablón. --}}
@php
    $statusColor = [
        'new' => '#2563eb', 'processing' => '#d97706', 'ready_for_pickup' => '#7c3aed',
        'shipped' => '#0891b2', 'completed' => '#16a34a', 'cancelled' => '#71717a',
    ][$order->status->value] ?? '#71717a';
@endphp
<span class="order-status inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold"
      style="background: color-mix(in srgb, {{ $statusColor }} 14%, transparent); color: color-mix(in srgb, {{ $statusColor }} 78%, currentColor)">
    <span class="size-1.5 rounded-full" style="background: {{ $statusColor }}" aria-hidden="true"></span>{{ $order->status->getLabel() }}
</span>
