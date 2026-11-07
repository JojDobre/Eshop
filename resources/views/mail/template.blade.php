@php
    // Tabuľky z premenných (napr. {order_items}) sa vykreslia komponentom e-mailu; ostatné je Markdown.
    $parts = preg_split('/<x-mail::table>(.*?)<\/x-mail::table>/s', $body, -1, PREG_SPLIT_DELIM_CAPTURE);
@endphp
<x-mail::message>
@foreach ($parts as $index => $part)
@if ($index % 2 === 1)
<x-mail::table>
{!! trim($part) !!}
</x-mail::table>
@else
{!! $part !!}
@endif
@endforeach
</x-mail::message>
