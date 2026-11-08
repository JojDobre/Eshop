<?php

use App\Core\Support\CurrencyContext;
use App\Core\Support\Price;
use App\Core\Themes\ThemeManager;

if (! function_exists('format_price')) {
    /**
     * Naformátuje cenu pre zobrazenie v šablóne, napr. format_price(1290) → „12,90 €".
     *
     * @param  int  $amount  Suma v najmenších jednotkách meny (centy, haliere).
     */
    function format_price(int $amount, ?string $currency = null): string
    {
        return Price::format($amount, $currency ?? app(CurrencyContext::class)->current());
    }
}

if (! function_exists('theme_asset')) {
    /**
     * Adresa súboru z priečinka assets/ šablóny (predvolene aktívnej), napr. theme_asset('css/theme.css').
     */
    function theme_asset(string $path, ?string $theme = null): string
    {
        $theme ??= app(ThemeManager::class)->active()?->name ?? (string) config('eshop.default_theme');

        return route('theme.asset', ['theme' => $theme, 'path' => ltrim($path, '/')]);
    }
}
