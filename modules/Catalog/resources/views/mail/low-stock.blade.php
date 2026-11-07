<x-mail::message>
# {{ __('catalog::admin.low_stock.mail_heading') }}

{{ __('catalog::admin.low_stock.mail_body', ['product' => $productName, 'count' => $count]) }}

<x-mail::button :url="$adminUrl">
{{ __('catalog::admin.low_stock.mail_open') }}
</x-mail::button>
</x-mail::message>
