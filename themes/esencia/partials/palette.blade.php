{{--
    Farebná paleta Esencie: premenné CSS pre celý obchod. Farba akcentu sa nastaví aj ako farba značky
    (--brand-primary), aby tlačidlá v košíku a pokladni mali rovnakú farbu. Text na farebných plochách je
    biely alebo tmavý podľa kontrastu (WCAG AA).
--}}
@php
    $palettes = [
        'koral' => ['ac' => '#ff5a3c', 'acd' => '#f2401f', 'onac' => '#14213d', 'ac2' => '#ffc93c', 'onac2' => '#14213d', 'ink' => '#14213d', 'muted' => '#566079', 'line' => '#e8eaf0',
            't1' => '#ffe4dc', 't2' => '#dde8ff', 't3' => '#fff1c2', 't4' => '#d9f5ea', 't5' => '#f1e3ff', 't6' => '#ffdbe6', 'font' => 'bricolage'],
        'lila' => ['ac' => '#6c3cff', 'acd' => '#5527e6', 'onac' => '#ffffff', 'ac2' => '#d7ff3a', 'onac2' => '#1a1530', 'ink' => '#1a1530', 'muted' => '#5a556e', 'line' => '#ebe8f3',
            't1' => '#ece5ff', 't2' => '#f4ffcc', 't3' => '#ffe0f0', 't4' => '#dff4ff', 't5' => '#fff0d6', 't6' => '#e3fff1', 'font' => 'unbounded'],
        'mata' => ['ac' => '#00805f', 'acd' => '#006a4f', 'onac' => '#ffffff', 'ac2' => '#ffd23f', 'onac2' => '#10261f', 'ink' => '#10261f', 'muted' => '#52625b', 'line' => '#e3ece8',
            't1' => '#d6f5ea', 't2' => '#fff3c4', 't3' => '#ffe1d6', 't4' => '#dcecff', 't5' => '#efe3ff', 't6' => '#e6f7d0', 'font' => 'outfit'],
        'jemna' => ['ac' => '#f7b2a6', 'acd' => '#f09a8b', 'onac' => '#2b2233', 'ac2' => '#c8e6d5', 'onac2' => '#2b2233', 'ink' => '#2b2233', 'muted' => '#655e6d', 'line' => '#efe9ee',
            't1' => '#fdece8', 't2' => '#eaf5ef', 't3' => '#fff4dc', 't4' => '#eef0fb', 't5' => '#f6ecf8', 't6' => '#fbeef3', 'font' => 'outfit'],
    ];

    $paletteName = (string) $themeOptions->get('palette', 'koral');

    if ($paletteName === 'brand') {
        $brand = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($branding->primary_color ?? '')) ? strtolower($branding->primary_color) : '#ff5a3c';
        // Relatívny jas farby značky rozhodne o farbe textu na nej.
        $channel = fn (int $value) => ($v = $value / 255) <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
        $luminance = 0.2126 * $channel(hexdec(substr($brand, 1, 2))) + 0.7152 * $channel(hexdec(substr($brand, 3, 2))) + 0.0722 * $channel(hexdec(substr($brand, 5, 2)));
        $palette = [...$palettes['koral'], 'ac' => $brand, 'acd' => "color-mix(in srgb, {$brand} 85%, black)", 'onac' => (1.05 / ($luminance + 0.05)) >= 4.5 ? '#ffffff' : '#14213d',
            't1' => "color-mix(in srgb, {$brand} 16%, white)", 'font' => 'bricolage'];
    } else {
        $palette = $palettes[$paletteName] ?? $palettes['koral'];
    }

    $font = (string) $themeOptions->get('heading_font', 'auto');
    $font = in_array($font, ['bricolage', 'unbounded', 'outfit'], true) ? $font : $palette['font'];
    $fonts = [
        'bricolage' => ["'Bricolage Grotesque Variable'", "'Inter Variable'", 800, '-0.03em'],
        'unbounded' => ["'Unbounded Variable'", "'Inter Variable'", 700, '-0.02em'],
        'outfit' => ["'Outfit Variable'", "'Outfit Variable'", 800, '-0.03em'],
    ][$font];
@endphp
<style>:root { --es-ac: {{ $palette['ac'] }}; --es-acd: {{ $palette['acd'] }}; --es-onac: {{ $palette['onac'] }}; --es-ac2: {{ $palette['ac2'] }}; --es-onac2: {{ $palette['onac2'] }}; --es-ink: {{ $palette['ink'] }}; --es-muted: {{ $palette['muted'] }}; --es-line: {{ $palette['line'] }};@foreach (range(1, 6) as $tint) --es-t{{ $tint }}: {{ $palette['t'.$tint] }};@endforeach --es-head: {!! $fonts[0] !!}, ui-sans-serif, system-ui, sans-serif; --theme-font: {!! $fonts[1] !!}, ui-sans-serif, system-ui, sans-serif; --es-head-weight: {{ $fonts[2] }}; --es-head-spacing: {{ $fonts[3] }}; --brand-primary: {{ $palette['ac'] }}; --brand-primary-foreground: {{ $palette['onac'] }}; }</style>
