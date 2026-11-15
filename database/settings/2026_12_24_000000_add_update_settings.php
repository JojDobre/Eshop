<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Automatické aktualizácie: inštalácia novej verzie cez cron (predvolene vypnutá) a upozornenie e-mailom.
        $this->migrator->add('system.updates_auto_install', false);
        $this->migrator->add('system.updates_notify', true);
        $this->migrator->add('system.updates_notified_version', null);
    }
};
