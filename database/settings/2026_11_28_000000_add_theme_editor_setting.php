<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Editor súborov šablóny je predvolene vypnutý (zapína sa v Nastavenia → Funkcie obchodu).
        $this->migrator->add('general.theme_editor_enabled', false);
    }
};
