<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Položky menu administrácie, ktoré obchodník skryl (cesty bez /admin/, napr. „manufacturers“).
        $this->migrator->add('general.admin_hidden_navigation', []);
    }
};
