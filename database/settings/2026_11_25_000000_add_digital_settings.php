<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Digitálne produkty: koľkokrát a ako dlho sa dá súbor stiahnuť, kedy sa odkazy pošlú.
        $this->migrator->add('catalog.digital_download_limit', 5);
        $this->migrator->add('catalog.digital_download_days', 30);
        $this->migrator->add('catalog.digital_deliver_unpaid', false);
    }
};
