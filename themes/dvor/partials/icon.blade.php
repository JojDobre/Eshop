{{-- Jednoduché ikony Dvora (čiarové SVG, farba podľa textu). --}}
@php
    $paths = [
        'check' => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        'leaf' => '<path d="M5 19c0-9 6-14 15-14 0 9-5 15-14 15M5 19l7-7"/>',
        'truck' => '<path d="M3 6h11v10H3zM14 9h4l3 3v4h-7"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/>',
        'pin' => '<path d="M12 21s7-6.2 7-11.5A7 7 0 0 0 5 9.5C5 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
        'calendar' => '<rect x="3.5" y="5" width="17" height="15" rx="3"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
        'heart' => '<path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10z"/>',
        'barn' => '<path d="M3 10 12 4l9 6v10H3z"/><path d="M9 20v-6h6v6M9 14l6 6M15 14l-6 6"/>',
        'egg' => '<path d="M12 3c3.5 0 6.5 5.5 6.5 10a6.5 6.5 0 0 1-13 0C5.5 8.5 8.5 3 12 3z"/>',
        'bag' => '<path d="M5 8h14l-1.2 11H6.2zM9 8a3 3 0 0 1 6 0"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'minus' => '<path d="M5 12h14"/>',
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
    ];
@endphp
<svg class="{{ $class ?? 'size-5' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $stroke ?? 2 }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $paths[$name] ?? $paths['check'] !!}</svg>
