<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('tax.vat_payer', true);
        $this->migrator->add('tax.prices_include_vat', true);
    }
};
