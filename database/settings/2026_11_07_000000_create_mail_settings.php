<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('mail.mailer', 'log');
        $this->migrator->add('mail.host', null);
        $this->migrator->add('mail.port', 587);
        $this->migrator->add('mail.encryption', 'tls');
        $this->migrator->add('mail.username', null);
        $this->migrator->addEncrypted('mail.password', null);
        $this->migrator->add('mail.from_address', null);
        $this->migrator->add('mail.from_name', null);
        $this->migrator->add('mail.notification_email', null);
    }
};
