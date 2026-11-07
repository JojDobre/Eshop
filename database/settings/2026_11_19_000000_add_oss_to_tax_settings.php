<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // OSS: DPH krajiny zákazníka pri predaji spotrebiteľom do iných krajín EÚ.
        $this->migrator->add('tax.oss_enabled', false);
        // Konečná cena ostane rovnaká (mení sa iba rozpis DPH), inak sa prepočíta z ceny bez DPH.
        $this->migrator->add('tax.oss_keep_gross_prices', true);
        // Prenesenie daňovej povinnosti pre firmy z EÚ s overeným IČ DPH (VIES).
        $this->migrator->add('tax.reverse_charge_enabled', true);
    }
};
