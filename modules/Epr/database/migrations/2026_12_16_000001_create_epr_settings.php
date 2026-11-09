<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Obal jednej zásielky (krabica, výplň, páska) v gramoch podľa materiálu.
        $this->migrator->add('epr.shipment_packaging', ['paper' => 0, 'plastic' => 0]);
    }
};
