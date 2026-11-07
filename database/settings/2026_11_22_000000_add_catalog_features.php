<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Funkcie katalógu (krok 4b), predvolene vypnuté – malý obchod ich nepotrebuje.
        foreach (['product_types', 'extra_custom_fields', 'addons', 'bundles', 'warehouses', 'batches', 'expiration', 'preorders', 'digital'] as $feature) {
            $this->migrator->add('catalog.feature_'.$feature, false);
        }

        // Expirácia: upozornenie a automatické úpravy tovaru s blížiacim sa dátumom spotreby.
        $this->migrator->add('catalog.expiration_warning_days', 14);
        $this->migrator->add('catalog.expiration_discount_percent', 0);
        $this->migrator->add('catalog.expiration_hide_days', 0);
        $this->migrator->add('catalog.expiration_show_date', true);
    }
};
