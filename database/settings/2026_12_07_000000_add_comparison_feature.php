<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Porovnávač produktov (krok 4e), predvolene vypnutý – zapína sa pre veľký obchod.
        $this->migrator->add('catalog.feature_comparison', false);
    }
};
