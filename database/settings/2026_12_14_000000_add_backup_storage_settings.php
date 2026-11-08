<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Ako často sa záloha robí automaticky: daily, weekly.
        $this->migrator->add('system.backups_frequency', 'daily');
        // Heslo na šifrovanie zálohy (AES-256). Prázdne = bez šifrovania.
        $this->migrator->addEncrypted('system.backups_password', null);
        // Externé úložisko: none, ftp, sftp, s3.
        $this->migrator->add('system.backups_remote', 'none');
        $this->migrator->add('system.backups_remote_host', null);
        $this->migrator->add('system.backups_remote_port', null);
        $this->migrator->add('system.backups_remote_username', null);
        $this->migrator->addEncrypted('system.backups_remote_password', null);
        $this->migrator->add('system.backups_remote_root', null);
        $this->migrator->add('system.backups_remote_bucket', null);
        $this->migrator->add('system.backups_remote_region', null);
        $this->migrator->add('system.backups_remote_endpoint', null);
        $this->migrator->add('system.backups_remote_keep', 14);
        // Výsledok poslednej kontroly zálohy (test obnovy).
        $this->migrator->add('system.backups_last_check', null);
    }
};
