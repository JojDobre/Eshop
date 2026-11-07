<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('translator.provider', 'none');
        $this->migrator->addEncrypted('translator.api_key', null);
        $this->migrator->add('translator.monthly_limit', 500000);
        $this->migrator->add('translator.used_characters', 0);
        $this->migrator->add('translator.usage_month', null);
        $this->migrator->add('translator.glossary', []);
    }
};
