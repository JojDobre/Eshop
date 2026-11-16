{{--
    Farebná paleta Dvora: premenné CSS pre celý obchod. Akcent sa nastaví aj ako farba značky (--brand-primary).
    Paleta Noc je tmavá – značka data-dv-dark prepne aj prvky predvolenej šablóny (karty, polia formulárov) na tmavé.
--}}
@php
    $palettes = [
        'luka' => ['bg' => '#ffffff', 'card' => '#ffffff', 'ac' => '#1a7f44', 'acd' => '#146a38', 'actext' => '#1a7f44', 'onac' => '#ffffff', 'ac2' => '#e64426', 'ac2text' => '#c9361b', 'onac2' => '#ffffff', 'sun' => '#ffc83d', 'onsun' => '#2a1d00', 'ink' => '#13261a', 'muted' => '#536659', 'line' => '#e3eee6', 'soft' => '#ecf7ef', 'soft2' => '#fff3cc', 'deep' => '#0f3d24', 'dark' => false],
        'paradajka' => ['bg' => '#ffffff', 'card' => '#ffffff', 'ac' => '#d63a22', 'acd' => '#b8301b', 'actext' => '#c2321b', 'onac' => '#ffffff', 'ac2' => '#1a7f44', 'ac2text' => '#1a7f44', 'onac2' => '#ffffff', 'sun' => '#ffc83d', 'onsun' => '#2a1d00', 'ink' => '#2a1410', 'muted' => '#6b544e', 'line' => '#f3e3df', 'soft' => '#fff0ec', 'soft2' => '#eaf6ec', 'deep' => '#5c160c', 'dark' => false],
        'obloha' => ['bg' => '#ffffff', 'card' => '#ffffff', 'ac' => '#1d64c8', 'acd' => '#1853a8', 'actext' => '#1d64c8', 'onac' => '#ffffff', 'ac2' => '#ff8a1f', 'ac2text' => '#b85400', 'onac2' => '#231200', 'sun' => '#ffd23f', 'onsun' => '#231a00', 'ink' => '#0f1f38', 'muted' => '#4f5f77', 'line' => '#e2eaf6', 'soft' => '#eaf2ff', 'soft2' => '#fff4d1', 'deep' => '#0d2f63', 'dark' => false],
        'noc' => ['bg' => '#0e1712', 'card' => '#16241b', 'ac' => '#ffc83d', 'acd' => '#f5b81f', 'actext' => '#ffc83d', 'onac' => '#231800', 'ac2' => '#ff7a59', 'ac2text' => '#ff8f73', 'onac2' => '#230b00', 'sun' => '#ffc83d', 'onsun' => '#231800', 'ink' => '#eef5ef', 'muted' => '#9fb3a5', 'line' => '#24382b', 'soft' => '#18291f', 'soft2' => '#172634', 'deep' => '#08100b', 'dark' => true],
        'jemna' => ['bg' => '#ffffff', 'card' => '#ffffff', 'ac' => '#467d58', 'acd' => '#3a6849', 'actext' => '#3f7350', 'onac' => '#ffffff', 'ac2' => '#e98a78', 'ac2text' => '#b0503d', 'onac2' => '#2a0f09', 'sun' => '#f6d88a', 'onsun' => '#2a2008', 'ink' => '#1f2a23', 'muted' => '#5c6a61', 'line' => '#e7efe9', 'soft' => '#f0f6f1', 'soft2' => '#fdf0ec', 'deep' => '#2f5a3e', 'dark' => false],
    ];

    $paletteName = (string) $themeOptions->get('palette', 'luka');

    if ($paletteName === 'brand') {
        $brand = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($branding->primary_color ?? '')) ? strtolower($branding->primary_color) : '#1a7f44';
        // Relatívny jas farby značky rozhodne o farbe textu na nej.
        $channel = fn (int $value) => ($v = $value / 255) <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
        $luminance = 0.2126 * $channel(hexdec(substr($brand, 1, 2))) + 0.7152 * $channel(hexdec(substr($brand, 3, 2))) + 0.0722 * $channel(hexdec(substr($brand, 5, 2)));
        $light = (1.05 / ($luminance + 0.05)) < 4.5;
        $palette = [...$palettes['luka'], 'ac' => $brand, 'acd' => "color-mix(in srgb, {$brand} 85%, black)", 'onac' => $light ? '#13261a' : '#ffffff',
            'actext' => $light ? "color-mix(in srgb, {$brand} 45%, black)" : $brand, 'soft' => "color-mix(in srgb, {$brand} 10%, white)",
            'deep' => "color-mix(in srgb, {$brand} 45%, black)"];
    } else {
        $palette = $palettes[$paletteName] ?? $palettes['luka'];
    }

    $font = [
        'fredoka' => ["'Fredoka Variable'", 600, '-0.01em'],
        'outfit' => ["'Outfit Variable'", 700, '-0.02em'],
        'young' => ["'Young Serif'", 400, '-0.01em'],
    ][(string) $themeOptions->get('heading_font', 'fredoka')] ?? ["'Fredoka Variable'", 600, '-0.01em'];
@endphp
<style>:root { @foreach (['bg', 'card', 'ac', 'acd', 'actext', 'onac', 'ac2', 'ac2text', 'onac2', 'sun', 'onsun', 'ink', 'muted', 'line', 'soft', 'soft2', 'deep'] as $var)--dv-{{ $var }}: {{ $palette[$var] }}; @endforeach --dv-head: {!! $font[0] !!}, ui-rounded, system-ui, sans-serif; --dv-head-weight: {{ $font[1] }}; --dv-head-spacing: {{ $font[2] }}; --brand-primary: {{ $palette['ac'] }}; --brand-primary-foreground: {{ $palette['onac'] }}; @if ($palette['dark']) color-scheme: dark; @endif }</style>
@if ($palette['dark'])
    <span data-dv-dark hidden></span>
@endif
