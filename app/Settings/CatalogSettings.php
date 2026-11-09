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

    public bool $feature_comparison;

    /** Koľko dní pred expiráciou upozorniť. */
    public int $expiration_warning_days;

    /** Automatická zľava (%) na tovar v lehote upozornenia (0 = bez zľavy). */
    public int $expiration_discount_percent;

    /** Skryť tovar X dní pred expiráciou (0 = neskrývať). */
    public int $expiration_hide_days;

    /** Zobraziť na webe „trvanlivosť minimálne do …“. */
    public bool $expiration_show_date;

    /** Koľkokrát sa dá súbor digitálneho produktu stiahnuť (0 = bez obmedzenia). */
    public int $digital_download_limit;

    /** Koľko dní platí odkaz na stiahnutie (0 = bez obmedzenia). */
    public int $digital_download_days;

    /** Poslať odkazy hneď po objednávke, nielen po zaplatení. */
    public bool $digital_deliver_unpaid;

    /** Zobrazenie produktu v obchode. */
    public bool $show_vat_note;

    public bool $show_stock_status;

    public bool $show_stock_quantity;

    public bool $show_sku;

    /** below = popis pod fotkami na celú šírku, side = vedľa fotiek. */
    public string $description_position;

    /** Štítok „Novinka“ pre produkty pridané za posledných N dní (0 = vypnuté). */
    public int $new_label_days;

    public static function group(): string
    {
        return 'catalog';
    }

    public function enabled(string $feature): bool
    {
        return (bool) ($this->{'feature_'.$feature} ?? false);
    }
}
