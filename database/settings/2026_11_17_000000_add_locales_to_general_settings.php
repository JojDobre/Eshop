<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Ďalšie jazyky obchodu (predvolený jazyk je vždy zapnutý).
        $this->migrator->add('general.locales', []);
    }
};
