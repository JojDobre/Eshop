<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('currencies.enabled', []);
        $this->migrator->add('currencies.rates', []);
        $this->migrator->add('currencies.rounding', []);
        $this->migrator->add('currencies.auto_update', true);
        $this->migrator->add('currencies.rates_updated_at', null);
    }
};
