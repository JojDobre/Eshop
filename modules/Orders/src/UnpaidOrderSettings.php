<?php

namespace Modules\Orders;

use Spatie\LaravelSettings\Settings;
use Throwable;

/**
 * Nezaplatené objednávky s platbou prevodom: po koľkých dňoch poslať pripomienku a po koľkých ich zrušiť.
 * Hodnota 0 vypína pripomienku alebo zrušenie.
 */
class UnpaidOrderSettings extends Settings
{
    public int $reminder_after_days;

    public int $cancel_after_days;

    public static function group(): string
    {
        return 'unpaid_orders';
    }

    public static function current(): ?self
    {
        try {
            return app(self::class);
        } catch (Throwable) {
            return null;
        }
    }
}
