{{--
    Farebná paleta Lupeňa: premenné CSS pre celý obchod. Akcent sa nastaví aj ako farba značky (--brand-primary).
    Paleta Noc je tmavá – značka data-lp-dark prepne aj prvky predvolenej šablóny (karty, polia formulárov) na tmavé.
--}}
@php
    $palettes = [
        'ruza' => ['bg' => '#ffffff', 'card' => '#ffffff', 'ac' => '#c8175f', 'acd' => '#a8104e', 'actext' => '#c8175f', 'onac' => '#ffffff', 'ac2' => '#ffd84d', 'onac2' => '#2a0f1c', 'ink' => '#2a0f1c', 'muted' => '#6a5260', 'line' => '#f0e4e9', 'soft' => '#fde8f0', 'soft2' => '#eef6ec', 'dark' => false],
        'luka' => ['bg' => '#ffffff', 'card' => '#ffffff', 'ac' => '#1d6b44', 'acd' => '#155535', 'actext' => '#1d6b44', 'onac' => '#ffffff', 'ac2' => '#ffcf3f', 'onac2' => '#14251b', 'ink' => '#14251b', 'muted' => '#52625a', 'line' => '#e2ece5', 'soft' => '#e5f3ea', 'soft2' => '#fff6d6', 'dark' => false],
        'noc' => ['bg' => '#0f1c15', 'card' => '#16271e', 'ac' => '#ff7aa8', 'acd' => '#ff5c93', 'actext' => '#ff7aa8', 'onac' => '#1a0c12', 'ac2' => '#d8f36b', 'onac2' => '#0f1c15', 'ink' => '#f4efe9', 'muted' => '#aab8ae', 'line' => '#24382c', 'soft' => '#1b3125', 'soft2' => '#2a1d24', 'dark' => true],
        'jemna' => ['bg' => '#ffffff', 'card' => '#ffffff', 'ac' => '#f2a5b8', 'acd' => '#ec8ea5', 'actext' => '#b43a5c', 'onac' => '#2b1a20', 'ac2' => '#c9e4cf', 'onac2' => '#1f2a22', 'ink' => '#2b1a20', 'muted' => '#685960', 'line' => '#f3e8ec', 'soft' => '#fdf0f4', 'soft2' => '#eff7f1', 'dark' => false],
    ];

    $paletteName = (string) $themeOptions->get('palette', 'ruza');

    if ($paletteName === 'brand') {
        $brand = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($branding->primary_color ?? '')) ? strtolower($branding->primary_color) : '#c8175f';
        // Relatívny jas farby značky rozhodne o farbe textu na nej.
        $channel = fn (int $value) => ($v = $value / 255) <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
        $luminance = 0.2126 * $channel(hexdec(substr($brand, 1, 2))) + 0.7152 * $channel(hexdec(substr($brand, 3, 2))) + 0.0722 * $channel(hexdec(substr($brand, 5, 2)));
        $light = (1.05 / ($luminance + 0.05)) < 4.5;
        $palette = [...$palettes['ruza'], 'ac' => $brand, 'acd' => "color-mix(in srgb, {$brand} 85%, black)", 'onac' => $light ? '#2a0f1c' : '#ffffff',
            'actext' => $light ? "color-mix(in srgb, {$brand} 45%, black)" : $brand, 'soft' => "color-mix(in srgb, {$brand} 10%, white)"];
    } else {
        $palette = $palettes[$paletteName] ?? $palettes['ruza'];
    }

    $font = [
        'young' => ["'Young Serif'", 400, '-0.01em'],
        'fraunces' => ["'Fraunces Variable'", 500, '-0.015em'],
        'jakarta' => ["'Plus Jakarta Sans Variable'", 800, '-0.03em'],
    ][(string) $themeOptions->get('heading_font', 'young')] ?? ["'Young Serif'", 400, '-0.01em'];
@endphp
<style>:root { @foreach (['bg', 'card', 'ac', 'acd', 'actext', 'onac', 'ac2', 'onac2', 'ink', 'muted', 'line', 'soft', 'soft2'] as $var)--lp-{{ $var }}: {{ $palette[$var] }}; @endforeach --lp-head: {!! $font[0] !!}, Georgia, serif; --lp-head-weight: {{ $font[1] }}; --lp-head-spacing: {{ $font[2] }}; --brand-primary: {{ $palette['ac'] }}; --brand-primary-foreground: {{ $palette['onac'] }}; @if ($palette['dark']) color-scheme: dark; @endif }</style>
@if ($palette['dark'])
    <span data-lp-dark hidden></span>
@endif
