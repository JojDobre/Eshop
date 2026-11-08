<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('reviews.enabled', true);
        // Hodnotenie sa zobrazí až po schválení administrátorom.
        $this->migrator->add('reviews.require_approval', true);
        // Hodnotiť môžu iba zákazníci, ktorí produkt kúpili.
        $this->migrator->add('reviews.verified_only', false);
        // Žiadosť o hodnotenie e-mailom po dokončení objednávky.
        $this->migrator->add('reviews.request_enabled', true);
        $this->migrator->add('reviews.request_delay_days', 7);
        // Informácia, ako obchod overuje hodnotenia (smernica Omnibus); prázdne = predvolený text.
        $this->migrator->add('reviews.verification_info', null);
    }
};
