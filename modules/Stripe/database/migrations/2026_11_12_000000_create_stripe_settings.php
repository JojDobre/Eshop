<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('stripe.enabled', false);
        $this->migrator->add('stripe.publishable_key', null);
        $this->migrator->addEncrypted('stripe.secret_key', null);
        $this->migrator->addEncrypted('stripe.webhook_secret', null);
    }
};
