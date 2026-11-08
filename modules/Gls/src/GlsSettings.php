<?php

namespace Modules\Gls;

use Spatie\LaravelSettings\Settings;

class GlsSettings extends Settings
{
    public string $country;

    public ?string $username;

    public ?string $password;

    public ?string $client_number;

    public bool $test_mode;

    public string $printer;

    public bool $notify_recipient;

    public static function group(): string
    {
        return 'gls';
    }

    /**
     * @return list<string>
     */
    public static function encrypted(): array
    {
        return ['password'];
    }
}
