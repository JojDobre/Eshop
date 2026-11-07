<x-mail::message>
# {{ __('catalog::admin.low_stock.mail_heading') }}

{{ __('catalog::admin.low_stock.mail_body', ['product' => $product->name, 'count' => $product->stock_quantity]) }}

<x-mail::button :url="$adminUrl">
{{ __('catalog::admin.low_stock.mail_open') }}
</x-mail::button>
</x-mail::message>
