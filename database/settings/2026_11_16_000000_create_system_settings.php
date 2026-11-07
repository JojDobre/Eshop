<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('system.backups_enabled', true);
        $this->migrator->add('system.backups_keep', 7);
        $this->migrator->add('system.backups_include_files', true);
    }
};
