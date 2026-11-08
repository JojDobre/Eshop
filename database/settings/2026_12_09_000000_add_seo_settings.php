<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Šablóny titulku a popisu, keď záznam nemá vlastné SEO údaje. Premenné: {name}, {category}, {shop}, {price}, {manufacturer}.
        $this->migrator->add('seo.product_title', '{name}');
        $this->migrator->add('seo.product_description', null);
        $this->migrator->add('seo.category_title', '{name}');
        $this->migrator->add('seo.category_description', null);
        // Pri zmene adresy produktu, kategórie alebo stránky sa stará adresa presmeruje (301).
        $this->migrator->add('seo.auto_redirects', true);
        // Filtrované a zoradené výpisy sa neindexujú (kanonická adresa bez parametrov).
        $this->migrator->add('seo.noindex_filtered', true);
        // Doplnkové riadky do robots.txt.
        $this->migrator->add('seo.robots_extra', null);
    }
};
