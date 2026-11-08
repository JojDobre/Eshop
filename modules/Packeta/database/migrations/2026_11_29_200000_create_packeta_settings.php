<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // API kľúč (pre mapu výdajných miest) a API heslo (zásielky) z klientskej sekcie Packeta.
        $this->migrator->add('packeta.api_key', null);
        $this->migrator->addEncrypted('packeta.api_password', null);
        // Označenie odosielateľa (eshop) z klientskej sekcie.
        $this->migrator->add('packeta.sender', null);
        $this->migrator->add('packeta.label_format', 'A6 on A6');
    }
};
