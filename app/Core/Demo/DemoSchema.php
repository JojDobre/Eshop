<?php

namespace App\Core\Demo;

/**
 * Čo všetko patrí do demo balíka a ako sú tabuľky prepojené.
 *
 * Demo sa prenáša bez ID: pri importe dostane každý riadok nové ID a odkazy (cudzie kľúče, odkazy v blokoch
 * a v menu) sa prepíšu podľa mapy starých ID na nové. Objednávky, zákazníci a nastavenia platieb sa neprenášajú.
 */
final class DemoSchema
{
    public const FORMAT = 1;

    /**
     * Tabuľky v poradí importu: [stĺpec => tabuľka, na ktorú odkazuje] (null = pri importe vynulovať).
     *
     * @var array<string, array<string, string|null>>
     */
    public const TABLES = [
        'manufacturers' => [],
        'product_labels' => [],
        'addons' => [],
        'categories' => ['parent_id' => 'categories'],
        // Typy produktov (merná jednotka pre cenu za liter, kilogram…).
        'product_types' => [],
        'products' => ['manufacturer_id' => 'manufacturers', 'product_type_id' => 'product_types', 'tax_rate_id' => null, 'availability_status_id' => null],
        'product_options' => ['product_id' => 'products'],
        'product_option_values' => ['product_option_id' => 'product_options'],
        'product_variants' => ['product_id' => 'products'],
        'product_prices' => ['product_id' => 'products', 'product_variant_id' => 'product_variants'],
        'forms' => [],
        // Vlastné polia objednávky v pokladni (napr. deň doručenia, text kartičky); polia produktov sa neprenášajú.
        'custom_fields' => [],
        'saved_blocks' => [],
        'blog_categories' => [],
        'blog_tags' => [],
        'blog_posts' => ['blog_category_id' => 'blog_categories', 'author_id' => null],
        'pages' => [],
        'block_layouts' => [],
        'menu_items' => ['parent_id' => 'menu_items'],
        // Rezervácie (iba keď je modul zapnutý).
        'booking_resources' => [],
        'booking_services' => [],
    ];

    /**
     * Väzobné tabuľky bez vlastného ID.
     *
     * @var array<string, array<string, string>>
     */
    public const PIVOTS = [
        'category_product' => ['category_id' => 'categories', 'product_id' => 'products'],
        'product_variant_values' => ['product_variant_id' => 'product_variants', 'product_option_value_id' => 'product_option_values'],
        'addon_product' => ['addon_id' => 'addons', 'product_id' => 'products'],
        'product_label_product' => ['product_label_id' => 'product_labels', 'product_id' => 'products'],
        'blog_post_tag' => ['blog_post_id' => 'blog_posts', 'blog_tag_id' => 'blog_tags'],
        'blog_post_product' => ['blog_post_id' => 'blog_posts', 'product_id' => 'products'],
        'blog_post_catalog_category' => ['blog_post_id' => 'blog_posts', 'category_id' => 'categories'],
        'booking_resource_service' => ['booking_resource_id' => 'booking_resources', 'booking_service_id' => 'booking_services'],
    ];

    /**
     * Kľúče v blokoch (JSON), ktoré obsahujú ID záznamov.
     *
     * @var array<string, string>
     */
    public const BLOCK_REFERENCES = [
        'category_id' => 'categories',
        'category_ids' => 'categories',
        'product_ids' => 'products',
        'form_id' => 'forms',
        'saved_block_id' => 'saved_blocks',
        'blog_category_id' => 'blog_categories',
        'post_ids' => 'blog_posts',
        // Nastavenie šablóny Dielňa: formulár v sekcii Výroba na mieru.
        'custom_form' => 'forms',
        'service_ids' => 'booking_services',
        // Nastavenie šablóny Esencia: produkty v sekcii Rutina v 3 krokoch.
        'routine_product_1' => 'products',
        'routine_product_2' => 'products',
        'routine_product_3' => 'products',
        // Nastavenie šablóny Lupeň: kategória v sekcii Izbové rastliny.
        'plants_category' => 'categories',
        // Nastavenia šablóny Kapitola: kniha mesiaca, kategórie sekcií a odporúčané knihy.
        'month_product' => 'products',
        'shelf_category' => 'categories',
        'digital_category' => 'categories',
        'stationery_category' => 'categories',
        'pick_1_product' => 'products',
        'pick_2_product' => 'products',
        'pick_3_product' => 'products',
        // Nastavenia šablóny Dvor: kategória produktov a zvieratiek na adopciu.
        'products_category' => 'categories',
        'animals_category' => 'categories',
        // Nastavenie šablóny Butik: produkty v sekcii Shop the look.
        'look_product_1' => 'products',
        'look_product_2' => 'products',
        'look_product_3' => 'products',
    ];

    /** Stĺpce s blokmi alebo nastaveniami, v ktorých sa prepisujú ID. */
    public const JSON_COLUMNS_WITH_REFERENCES = ['blocks', 'values'];

    /**
     * Typ položky menu → tabuľka cieľa (stĺpec target obsahuje ID).
     *
     * @var array<string, string>
     */
    public const MENU_TARGETS = [
        'page' => 'pages',
        'category' => 'categories',
        'product' => 'products',
        'post' => 'blog_posts',
        'blog_category' => 'blog_categories',
    ];

    /** Stĺpce, ktoré sa neprenášajú (pri importe sa nastavia nanovo). */
    public const SKIPPED_COLUMNS = ['id', 'created_at', 'updated_at', 'preview_token', 'reviews_count', 'rating_average'];

    /** Skupiny nastavení prenášané s demom (logo, farba). */
    public const SETTINGS_GROUPS = ['branding'];

    /** Priečinky verejného disku, z ktorých sa berú obrázky. */
    public const MEDIA_PATTERN = '#(?<![\w.-])((?:media|demo|products|categories|blog|blocks|theme|branding|variants|services)/[\w\-./]+?\.(?:jpe?g|png|webp|gif|svg|avif))#i';

    /** Zástupný text za adresu priečinka s nahranými súbormi (v obsahu sa adresa obchodu líši). */
    public const UPLOADS_PLACEHOLDER = '{{uploads}}/';
}
