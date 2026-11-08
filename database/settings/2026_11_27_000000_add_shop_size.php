<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Veľkosť obchodu (small, medium, large, custom). Doterajšie obchody sú malé.
        $this->migrator->add('general.shop_size', 'small');
        // Veľkosť zvolená v inštalátore sa použije pri prvej požiadavke (keď sú načítané všetky moduly).
        $this->migrator->add('general.shop_size_pending', false);
    }
};
