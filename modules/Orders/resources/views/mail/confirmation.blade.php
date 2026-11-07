@php($money = fn (int $amount) => \App\Core\Support\Price::format($amount, $order->currency))
<x-mail::message>
# {{ __('orders::mail.confirmation.heading') }}

{{ __('orders::mail.confirmation.intro', ['name' => $order->billing_name, 'number' => $order->number]) }}

<x-mail::table>
| {{ __('orders::mail.product') }} | {{ __('orders::mail.quantity') }} | {{ __('orders::mail.price') }} |
|:--|:-:|--:|
@foreach ($order->items as $item)
| {{ $item->fullName() }} | {{ $item->quantity }} | {{ $money($item->total) }} |
@endforeach
| {{ $order->shipping_method_name }} | | {{ $money($order->shipping_price) }} |
@if ($order->payment_fee > 0)
| {{ $order->payment_method_name }} | | {{ $money($order->payment_fee) }} |
@endif
| **{{ __('orders::mail.total') }}** | | **{{ $money($order->total) }}** |
</x-mail::table>

@if ($order->payment_type === \Modules\Payments\PaymentType::BankTransfer && ! $order->isPaid())
## {{ __('orders::mail.bank.heading') }}

@if ($company->iban)
{{ __('orders::mail.bank.account') }}: **{{ $company->iban }}**@if ($company->swift) (SWIFT {{ $company->swift }})@endif<br>
@endif
{{ __('orders::mail.bank.variable_symbol') }}: **{{ $order->variableSymbol() }}**<br>
{{ __('orders::mail.bank.amount') }}: **{{ $money($order->total) }}**
@endif

@if ($order->payment_instructions)
{{ $order->payment_instructions }}
@endif

@if ($order->shipping_details)
## {{ $order->shipping_method_name }}

{!! nl2br(e($order->shipping_details)) !!}
@endif

<x-mail::button :url="$order->publicUrl()">
{{ __('orders::mail.confirmation.view_order') }}
</x-mail::button>

{{ __('orders::mail.confirmation.thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>
