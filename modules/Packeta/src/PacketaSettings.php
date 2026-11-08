<?php

namespace Modules\Packeta;

use Spatie\LaravelSettings\Settings;

class PacketaSettings extends Settings
{
    public ?string $api_key;

    public ?string $api_password;

    public ?string $sender;

    public string $label_format;

    public static function group(): string
    {
        return 'packeta';
    }

    /**
     * @return list<string>
     */
    public static function encrypted(): array
    {
        return ['api_password'];
    }
}
