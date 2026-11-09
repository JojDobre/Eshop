<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Čo sa zobrazuje na produkte v obchode.
        $this->migrator->add('catalog.show_vat_note', true);
        $this->migrator->add('catalog.show_stock_status', true);
        $this->migrator->add('catalog.show_stock_quantity', true);
        $this->migrator->add('catalog.show_sku', true);
        // Popis produktu: pod fotkami a cenou na celú šírku (below), alebo vedľa fotiek (side).
        $this->migrator->add('catalog.description_position', 'below');
        // Automatický štítok „Novinka“ pre produkty pridané za posledných N dní (0 = vypnuté).
        $this->migrator->add('catalog.new_label_days', 30);
    }
};
