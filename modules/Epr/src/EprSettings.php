<?php

namespace Modules\Epr;

use Spatie\LaravelSettings\Settings;

class EprSettings extends Settings
{
    /** Obal jednej zásielky v gramoch podľa materiálu. */
    public array $shipment_packaging;

    public static function group(): string
    {
        return 'epr';
    }
}
