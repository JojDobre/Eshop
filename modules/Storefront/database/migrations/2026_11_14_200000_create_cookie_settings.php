<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('cookies.enabled', false);
        $this->migrator->add('cookies.analytics_code', null);
        $this->migrator->add('cookies.marketing_code', null);
    }
};
