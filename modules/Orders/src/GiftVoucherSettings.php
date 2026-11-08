<?php

namespace Modules\Orders;

use Spatie\LaravelSettings\Settings;

class GiftVoucherSettings extends Settings
{
    public bool $enabled;

    public int $validity_months;

    public static function group(): string
    {
        return 'gift_vouchers';
    }

    public static function isEnabled(): bool
    {
        try {
            return app(self::class)->enabled;
        } catch (\Throwable) {
            return false;
        }
    }
}
