<?php

namespace Modules\Balikobot;

use Spatie\LaravelSettings\Settings;

class BalikobotSettings extends Settings
{
    public ?string $api_user;

    public ?string $api_key;

    public bool $auto_order;

    public static function group(): string
    {
        return 'balikobot';
    }

    /**
     * @return list<string>
     */
    public static function encrypted(): array
    {
        return ['api_key'];
    }
}
