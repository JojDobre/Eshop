<?php

namespace App\Settings;

use Illuminate\Support\Facades\Storage;
use Spatie\LaravelSettings\Settings;

/**
 * Vzhľad značky: logo, favicon a hlavná farba. Používa ich obchod aj administrácia.
 */
class BrandingSettings extends Settings
{
    /** Cesta k logu na disku „public". */
    public ?string $logo;

    /** Logo pre tmavý režim. */
    public ?string $logo_dark;

    public ?string $favicon;

    /** Hlavná farba vo formáte #rrggbb. */
    public string $primary_color;

    public static function group(): string
    {
        return 'branding';
    }

    public const DEFAULT_PRIMARY_COLOR = '#4f46e5';

    /**
     * Hlavná farba overená na formát #rrggbb (bezpečná na vloženie do CSS).
     */
    public function primaryColor(): string
    {
        return preg_match('/^#[0-9a-fA-F]{6}$/', $this->primary_color) ? strtolower($this->primary_color) : self::DEFAULT_PRIMARY_COLOR;
    }

    /**
     * Farba textu na pozadí hlavnej farby (biela alebo takmer čierna podľa kontrastu, WCAG).
     */
    public function primaryForegroundColor(): string
    {
        [$r, $g, $b] = array_map(
            fn (string $hex) => hexdec($hex) / 255,
            str_split(substr($this->primaryColor(), 1), 2),
        );

        $channel = fn (float $c) => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        $luminance = 0.2126 * $channel($r) + 0.7152 * $channel($g) + 0.0722 * $channel($b);

        // Kontrast s bielou: 1.05 / (L + 0.05), s čiernou: (L + 0.05) / 0.05.
        return (1.05 / ($luminance + 0.05)) >= (($luminance + 0.05) / 0.05) ? '#ffffff' : '#4f46e5';
    }

    public function logoUrl(): ?string
    {
        return $this->url($this->logo);
    }

    public function darkLogoUrl(): ?string
    {
        return $this->url($this->logo_dark) ?? $this->logoUrl();
    }

    public function faviconUrl(): ?string
    {
        return $this->url($this->favicon);
    }

    private function url(?string $path): ?string
    {
        return $path ? Storage::disk('public')->url($path) : null;
    }
}
