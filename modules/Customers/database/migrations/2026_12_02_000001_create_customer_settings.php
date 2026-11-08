<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Zákaznícke skupiny a ceny pre skupiny (Nastavenia → Funkcie obchodu).
        $this->migrator->add('customers.groups_enabled', false);
    }
};
