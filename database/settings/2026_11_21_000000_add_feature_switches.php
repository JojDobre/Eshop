<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Hlavné vypínače: vypnutie nemaže vybrané jazyky, kurzy ani ručné ceny.
        $this->migrator->add('general.locales_enabled', true);
        $this->migrator->add('currencies.multicurrency_enabled', true);
    }
};
