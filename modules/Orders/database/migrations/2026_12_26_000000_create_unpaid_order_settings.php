<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    // Pripomienka po 3 dňoch je zapnutá, automatické zrušenie si obchodník zapne sám.
    public function up(): void
    {
        $this->migrator->add('unpaid_orders.reminder_after_days', 3);
        $this->migrator->add('unpaid_orders.cancel_after_days', 0);
    }
};
