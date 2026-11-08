<?php

namespace Modules\Reservations;

use Spatie\LaravelSettings\Settings;

class ReservationSettings extends Settings
{
    public int $horizon_days;

    public int $lead_hours;

    public int $cancel_hours;

    public int $reminder_hours;

    public int $product_hold_days;

    public int $product_deposit;

    public string $ical_token;

    public static function group(): string
    {
        return 'reservations';
    }
}
