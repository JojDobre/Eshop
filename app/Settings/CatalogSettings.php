<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class CatalogSettings extends Settings
{
    /** Pod koľko kusov sa produkt považuje za „dochádzajúci" (upozornenie obchodníkovi). */
    public int $low_stock_threshold;

    /** Typy produktov (sady polí, merná jednotka, bez dopravy, jedinečný kus). */
    public bool $feature_product_types;

    /** Vlastné polia aj pre kategórie, výrobcov, zákazníkov a objednávky. */
    public bool $feature_extra_custom_fields;

    /** Doplnkové voľby k produktu s príplatkom (darčekové balenie, gravírovanie). */
    public bool $feature_addons;

    /** Balíčky viacerých produktov. */
    public bool $feature_bundles;

    /** Viac skladov alebo predajní. */
    public bool $feature_warehouses;

    /** Šarže (číslo šarže, dátum výroby a spotreby, dohľadanie). */
    public bool $feature_batches;

    /** Sledovanie expirácie (vyžaduje šarže). */
    public bool $feature_expiration;

    /** Predobjednávky pred naskladnením. */
    public bool $feature_preorders;

    /** Digitálne produkty na stiahnutie. */
    public bool $feature_digital;

    /** Koľko dní pred expiráciou upozorniť. */
    public int $expiration_warning_days;

    /** Automatická zľava (%) na tovar v lehote upozornenia (0 = bez zľavy). */
    public int $expiration_discount_percent;

    /** Skryť tovar X dní pred expiráciou (0 = neskrývať). */
    public int $expiration_hide_days;

    /** Zobraziť na webe „trvanlivosť minimálne do …“. */
    public bool $expiration_show_date;

    public static function group(): string
    {
        return 'catalog';
    }

    public function enabled(string $feature): bool
    {
        return (bool) ($this->{'feature_'.$feature} ?? false);
    }
}
