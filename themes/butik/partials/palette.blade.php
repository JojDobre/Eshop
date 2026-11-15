{{--
    Farebná paleta Butiku: premenné CSS pre celý obchod. Akcent sa nastaví aj ako farba značky (--brand-primary),
    aby tlačidlá v košíku a pokladni mali rovnakú farbu. Text na akcente je biely alebo tmavý podľa kontrastu (WCAG AA).
--}}
@php
    $palettes = [
        'cierna' => ['ac' => '#e0261b', 'acd' => '#c01d13', 'actext' => '#e0261b', 'onac' => '#ffffff', 'ink' => '#0d0d0d', 'muted' => '#5f5f5f', 'line' => '#e6e6e6', 'soft' => '#f4f4f4'],
        'kobalt' => ['ac' => '#2541ff', 'acd' => '#1730d6', 'actext' => '#2541ff', 'onac' => '#ffffff', 'ink' => '#0b0d1a', 'muted' => '#5a5d70', 'line' => '#e4e6ef', 'soft' => '#f2f4fb'],
        'ruza' => ['ac' => '#ff4fa3', 'acd' => '#f2338e', 'actext' => '#c8106c', 'onac' => '#0d0d0d', 'ink' => '#0d0d0d', 'muted' => '#5f5f5f', 'line' => '#ececec', 'soft' => '#fff0f7'],
        'jemna' => ['ac' => '#c9b8ff', 'acd' => '#b6a0fb', 'actext' => '#6a4fd8', 'onac' => '#16131f', 'ink' => '#16131f', 'muted' => '#625e74', 'line' => '#ebe8f2', 'soft' => '#f6f4fb'],
    ];

    $paletteName = (string) $themeOptions->get('palette', 'cierna');

    if ($paletteName === 'brand') {
        $brand = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($branding->primary_color ?? '')) ? strtolower($branding->primary_color) : '#e0261b';
        // Relatívny jas farby značky rozhodne o farbe textu na nej.
        $channel = fn (int $value) => ($v = $value / 255) <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
        $luminance = 0.2126 * $channel(hexdec(substr($brand, 1, 2))) + 0.7152 * $channel(hexdec(substr($brand, 3, 2))) + 0.0722 * $channel(hexdec(substr($brand, 5, 2)));
        $light = (1.05 / ($luminance + 0.05)) < 4.5;
        $palette = [...$palettes['cierna'], 'ac' => $brand, 'acd' => "color-mix(in srgb, {$brand} 85%, black)", 'onac' => $light ? '#0d0d0d' : '#ffffff',
            // Svetlá farba značky sa na texte na bielom pozadí stmaví, aby bola čitateľná.
            'actext' => $light ? "color-mix(in srgb, {$brand} 45%, black)" : $brand,
            'soft' => "color-mix(in srgb, {$brand} 7%, white)"];
    } else {
        $palette = $palettes[$paletteName] ?? $palettes['cierna'];
    }

    $font = [
        'anton' => ["'Anton'", 400, '0.01em', 'uppercase', 0.92],
        'syne' => ["'Syne Variable'", 800, '-0.02em', 'none', 0.95],
        'archivo' => ["'Archivo Variable'", 800, '-0.02em', 'uppercase', 0.95],
    ][(string) $themeOptions->get('heading_font', 'anton')] ?? ["'Anton'", 400, '0.01em', 'uppercase', 0.92];
@endphp
<style>:root { --bt-ac: {{ $palette['ac'] }}; --bt-acd: {{ $palette['acd'] }}; --bt-actext: {{ $palette['actext'] }}; --bt-onac: {{ $palette['onac'] }}; --bt-ink: {{ $palette['ink'] }}; --bt-muted: {{ $palette['muted'] }}; --bt-line: {{ $palette['line'] }}; --bt-soft: {{ $palette['soft'] }}; --bt-head: {!! $font[0] !!}, Impact, 'Arial Narrow', sans-serif; --bt-head-weight: {{ $font[1] }}; --bt-head-spacing: {{ $font[2] }}; --bt-head-case: {{ $font[3] }}; --bt-head-leading: {{ $font[4] }}; --brand-primary: {{ $palette['ac'] }}; --brand-primary-foreground: {{ $palette['onac'] }}; }</style>
