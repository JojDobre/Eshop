<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('gift_vouchers.enabled', false);
        // Platnosť poukážky v mesiacoch (0 = bez obmedzenia).
        $this->migrator->add('gift_vouchers.validity_months', 12);
    }
};
