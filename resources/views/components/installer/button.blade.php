@props(['href' => null, 'variant' => 'primary'])

@php
    $classes = 'inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-900 focus-visible:ring-offset-2 disabled:opacity-60 '
        .($variant === 'primary' ? 'bg-zinc-900 text-white hover:bg-zinc-700' : 'border border-zinc-300 bg-white text-zinc-800 hover:bg-zinc-50');
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'submit', 'class' => $classes]) }}>{{ $slot }}</button>
@endif
