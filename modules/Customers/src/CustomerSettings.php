<?php

namespace Modules\Customers;

use Spatie\LaravelSettings\Settings;

class CustomerSettings extends Settings
{
    public bool $groups_enabled;

    public static function group(): string
    {
        return 'customers';
    }

    /** Sú zákaznícke skupiny zapnuté? (Pred migráciou nastavení sú vypnuté.) */
    public static function groupsEnabled(): bool
    {
        try {
            return app(self::class)->groups_enabled;
        } catch (\Throwable) {
            return false;
        }
    }
}
