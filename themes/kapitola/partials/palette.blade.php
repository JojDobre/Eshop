{{--
    Farebná paleta Kapitoly: premenné CSS pre celý obchod. Akcent sa nastaví aj ako farba značky (--brand-primary).
    Paleta Noc je tmavá – značka data-kp-dark prepne aj prvky predvolenej šablóny (karty, polia formulárov) na tmavé.
--}}
@php
    $palettes = [
        'bordo' => ['bg' => '#ffffff', 'card' => '#ffffff', 'ac' => '#7a1f2b', 'acd' => '#62161f', 'actext' => '#7a1f2b', 'onac' => '#ffffff', 'ac2' => '#e9a23b', 'onac2' => '#2a1608', 'ink' => '#24161a', 'muted' => '#675659', 'line' => '#efe6e7', 'soft' => '#f8eef0', 'soft2' => '#fde4bf', 'shelf' => '#6b3e26', 'dark' => false],
        'les' => ['bg' => '#ffffff', 'card' => '#ffffff', 'ac' => '#1f4d3a', 'acd' => '#173b2c', 'actext' => '#1f4d3a', 'onac' => '#ffffff', 'ac2' => '#b5562a', 'onac2' => '#ffffff', 'ink' => '#17231d', 'muted' => '#52605a', 'line' => '#e4ece7', 'soft' => '#eaf2ed', 'soft2' => '#fbe6d9', 'shelf' => '#5b3a24', 'dark' => false],
        'vecer' => ['bg' => '#14161f', 'card' => '#1d2030', 'ac' => '#f0b35a', 'acd' => '#eba23a', 'actext' => '#f0b35a', 'onac' => '#1b1308', 'ac2' => '#c9566b', 'onac2' => '#ffffff', 'ink' => '#f3ede4', 'muted' => '#a8a6b3', 'line' => '#2b2f43', 'soft' => '#1f2335', 'soft2' => '#2a2030', 'shelf' => '#4a3426', 'dark' => true],
        'jemna' => ['bg' => '#ffffff', 'card' => '#ffffff', 'ac' => '#a85466', 'acd' => '#8f4455', 'actext' => '#a85466', 'onac' => '#ffffff', 'ac2' => '#9db8a0', 'onac2' => '#18241b', 'ink' => '#2b2226', 'muted' => '#685c62', 'line' => '#f1e8ea', 'soft' => '#fbf1f3', 'soft2' => '#eff5f0', 'shelf' => '#8a6a55', 'dark' => false],
    ];

    $paletteName = (string) $themeOptions->get('palette', 'bordo');

    if ($paletteName === 'brand') {
        $brand = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($branding->primary_color ?? '')) ? strtolower($branding->primary_color) : '#7a1f2b';
        // Relatívny jas farby značky rozhodne o farbe textu na nej.
        $channel = fn (int $value) => ($v = $value / 255) <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
        $luminance = 0.2126 * $channel(hexdec(substr($brand, 1, 2))) + 0.7152 * $channel(hexdec(substr($brand, 3, 2))) + 0.0722 * $channel(hexdec(substr($brand, 5, 2)));
        $light = (1.05 / ($luminance + 0.05)) < 4.5;
        $palette = [...$palettes['bordo'], 'ac' => $brand, 'acd' => "color-mix(in srgb, {$brand} 85%, black)", 'onac' => $light ? '#24161a' : '#ffffff',
            'actext' => $light ? "color-mix(in srgb, {$brand} 45%, black)" : $brand, 'soft' => "color-mix(in srgb, {$brand} 10%, white)"];
    } else {
        $palette = $palettes[$paletteName] ?? $palettes['bordo'];
    }

    $font = [
        'playfair' => ["'Playfair Display Variable'", 600, '-0.01em'],
        'fraunces' => ["'Fraunces Variable'", 500, '-0.015em'],
        'young' => ["'Young Serif'", 400, '-0.01em'],
    ][(string) $themeOptions->get('heading_font', 'playfair')] ?? ["'Playfair Display Variable'", 600, '-0.01em'];
@endphp
<style>:root { @foreach (['bg', 'card', 'ac', 'acd', 'actext', 'onac', 'ac2', 'onac2', 'ink', 'muted', 'line', 'soft', 'soft2', 'shelf'] as $var)--kp-{{ $var }}: {{ $palette[$var] }}; @endforeach --kp-head: {!! $font[0] !!}, Georgia, serif; --kp-head-weight: {{ $font[1] }}; --kp-head-spacing: {{ $font[2] }}; --brand-primary: {{ $palette['ac'] }}; --brand-primary-foreground: {{ $palette['onac'] }}; @if ($palette['dark']) color-scheme: dark; @endif }</style>
@if ($palette['dark'])
    <span data-kp-dark hidden></span>
@endif
