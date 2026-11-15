{{-- Jednoduché ikony Lupeňa (čiarové SVG, farba podľa textu). --}}
@php
    $paths = [
        'check' => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        'flask' => '<path d="M9 3h6M10 3v6L4.6 18.2A1.8 1.8 0 0 0 6.2 21h11.6a1.8 1.8 0 0 0 1.6-2.8L14 9V3"/><path d="M7 15h10"/>',
        'leaf' => '<path d="M5 19c0-8 5-13 15-14-1 10-6 15-14 15"/><path d="M5 19c3-4 6-7 10-9"/>',
        'truck' => '<path d="M3 6h11v10H3zM14 9h4l3 3v4h-7"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/>',
        'gift' => '<path d="M4 10h16v10H4zM3 7h18v3H3zM12 7v13"/><path d="M12 7c-1.5-3-5-3-5-1s3 1 5 1c2 0 5 1 5-1s-3.5-2-5 1"/>',
        'heart' => '<path d="M12 20s-7-4.4-9-8.6C1.7 8.2 3.6 5 6.8 5c2 0 3.4 1.1 5.2 3 1.8-1.9 3.2-3 5.2-3 3.2 0 5.1 3.2 3.8 6.4C19 15.6 12 20 12 20z"/>',
        'star' => '<path d="m12 3.5 2.6 5.3 5.9.9-4.3 4.1 1 5.8-5.2-2.7-5.2 2.7 1-5.8L3.5 9.7l5.9-.9z"/>',
        'shield' => '<path d="M12 3 5 6v6c0 4.2 3 7.6 7 9 4-1.4 7-4.8 7-9V6z"/><path d="m9 12 2 2 4-4"/>',
        'recycle' => '<path d="M7 19H4.5l3-5M17 19h2.5l-3-5M9.5 5.5 12 2l2.5 3.5"/><path d="M7.5 14 12 6l4.5 8M7 19h10"/>',
        'drop' => '<path d="M12 3c3.5 4.4 6 7.8 6 11a6 6 0 0 1-12 0c0-3.2 2.5-6.6 6-11z"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'sparkle' => '<path d="M12 3v4M12 17v4M3 12h4M17 12h4M12 7c.8 3 2 4.2 5 5-3 .8-4.2 2-5 5-.8-3-2-4.2-5-5 3-.8 4.2-2 5-5z"/>',
        'camera' => '<path d="M4 8h3l2-3h6l2 3h3v11H4z"/><circle cx="12" cy="13" r="3.5"/>',
        'card' => '<rect x="3" y="6" width="18" height="12" rx="2"/><path d="M7 10h6M7 14h10"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
    ];
@endphp
<svg class="{{ $class ?? 'size-5' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $stroke ?? 1.8 }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $paths[$name] ?? $paths['check'] !!}</svg>
