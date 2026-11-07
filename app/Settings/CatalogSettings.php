<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class CatalogSettings extends Settings
{
    /** Pod koľko kusov sa produkt považuje za „dochádzajúci" (upozornenie obchodníkovi). */
    public int $low_stock_threshold;

    public static function group(): string
    {
        return 'catalog';
    }
}
