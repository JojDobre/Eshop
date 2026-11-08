<?php

namespace Modules\Sps;

use Spatie\LaravelSettings\Settings;

class SpsSettings extends Settings
{
    public ?string $username;

    public ?string $password;

    public string $service;

    public int $notify;

    public bool $insure;

    public ?string $cod_iban;

    public static function group(): string
    {
        return 'sps';
    }

    /**
     * @return list<string>
     */
    public static function encrypted(): array
    {
        return ['password'];
    }
}
