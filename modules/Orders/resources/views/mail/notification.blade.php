@php($money = fn (int $amount) => \App\Core\Support\Price::format($amount, $order->currency))
<x-mail::message>
# {{ __('orders::mail.notification.heading', ['number' => $order->number]) }}

**{{ $order->billing_name }}**@if ($order->billing_company), {{ $order->billing_company }}@endif<br>
{{ $order->email }}@if ($order->phone), {{ $order->phone }}@endif

<x-mail::table>
| {{ __('orders::mail.product') }} | {{ __('orders::mail.quantity') }} | {{ __('orders::mail.price') }} |
|:--|:-:|--:|
@foreach ($order->items as $item)
| {{ $item->fullName() }} | {{ $item->quantity }} | {{ $money($item->total) }} |
@endforeach
| **{{ __('orders::mail.total') }}** | | **{{ $money($order->total) }}** |
</x-mail::table>

{{ __('orders::mail.notification.shipping_payment', ['shipping' => $order->shipping_method_name, 'payment' => $order->payment_method_name]) }}

@if ($order->customer_note)
> {{ $order->customer_note }}
@endif

<x-mail::button :url="$adminUrl">
{{ __('orders::mail.notification.open') }}
</x-mail::button>
</x-mail::message>
