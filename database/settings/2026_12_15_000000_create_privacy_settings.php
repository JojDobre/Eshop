<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Prázdne = predvolené lehoty z jednotlivých pravidiel (App\Core\Privacy\DataRetention).
        $this->migrator->add('privacy.retention', []);
    }
};
