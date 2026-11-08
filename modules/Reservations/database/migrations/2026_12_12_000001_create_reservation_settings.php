<?php

use Illuminate\Support\Str;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Rezervovať sa dá najviac toľko dní dopredu a najskôr o toľko hodín.
        $this->migrator->add('reservations.horizon_days', 60);
        $this->migrator->add('reservations.lead_hours', 2);
        // Zákazník môže zrušiť rezerváciu najneskôr toľko hodín pred začiatkom.
        $this->migrator->add('reservations.cancel_hours', 24);
        // Pripomienka e-mailom toľko hodín pred začiatkom (0 = neposielať).
        $this->migrator->add('reservations.reminder_hours', 24);
        // Rezervácia jedinečného kusu (zviera, originál) na toľko dní.
        $this->migrator->add('reservations.product_hold_days', 3);
        $this->migrator->add('reservations.product_deposit', 0);
        // Tajný kľúč adresy kalendára iCal pre administrátora.
        $this->migrator->add('reservations.ical_token', Str::random(40));
    }
};
