<?php

use App\Core\Support\CurrencyContext;
use App\Core\Support\Price;

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
