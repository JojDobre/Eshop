<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // API používateľ a kľúč z klientskej zóny Balíkobota.
        $this->migrator->add('balikobot.api_user', null);
        $this->migrator->addEncrypted('balikobot.api_key', null);
        // Po vytvorení zásielky hneď odovzdať dáta kuriérovi (metóda order).
        $this->migrator->add('balikobot.auto_order', true);
    }
};
