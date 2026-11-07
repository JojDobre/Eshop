<?php

namespace App\Filament;

use Filament\Support\Colors\Color;

/**
 * Paleta pre administráciu, v ktorej je farba značky presne odtieň 600.
 *
 * Filament pre tlačidlá vyberá odtieň podľa kontrastu s textom. Pri automaticky
 * generovanej palete (farba = 500) mu vychádzal svetlý podklad s tmavým textom,
 * ktorý pôsobil ako neaktívne tlačidlo. Takto majú tlačidlá farbu značky.
 */
final class BrandPalette
{
    /** Podiel bielej (svetlejšie odtiene) alebo čiernej (tmavšie) primiešanej k farbe značky. */
    private const MIX = [
        50 => ['white', 0.95], 100 => ['white', 0.9], 200 => ['white', 0.78], 300 => ['white', 0.6],
        400 => ['white', 0.38], 500 => ['white', 0.18], 600 => ['white', 0.0],
        700 => ['black', 0.15], 800 => ['black', 0.3], 900 => ['black', 0.45], 950 => ['black', 0.62],
    ];

    /**
     * @return array<int, string>
     */
    public static function fromHex(string $hex): array
    {
        [$r, $g, $b] = array_map('hexdec', str_split(ltrim($hex, '#'), 2));
        $palette = [];

        foreach (self::MIX as $shade => [$target, $amount]) {
            $towards = $target === 'white' ? 255 : 0;
            $mix = fn (int $channel) => (int) round($channel + ($towards - $channel) * $amount);

            $palette[$shade] = Color::convertToOklch(sprintf('rgb(%d, %d, %d)', $mix($r), $mix($g), $mix($b)));
        }

        return $palette;
    }
}
