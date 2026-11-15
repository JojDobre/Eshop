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

        // Zálohy na Google Disk (OAuth klient zadaný správcom, tokeny šifrované).
        $this->migrator->add('system.backups_gdrive_client_id', null);
        $this->migrator->addEncrypted('system.backups_gdrive_client_secret', null);
        $this->migrator->addEncrypted('system.backups_gdrive_refresh_token', null);
        $this->migrator->add('system.backups_gdrive_folder_id', null);
        $this->migrator->add('system.backups_gdrive_account', null);
    }
};
