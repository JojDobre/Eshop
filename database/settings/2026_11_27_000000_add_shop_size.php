<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Veľkosť obchodu (small, medium, large, custom). Doterajšie obchody sú malé.
        $this->migrator->add('general.shop_size', 'small');
    }
};
