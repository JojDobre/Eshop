<?php

namespace Modules\SlovenskaPosta;

use Spatie\LaravelSettings\Settings;

class SlovenskaPostaSettings extends Settings
{
    public ?string $user_id;

    public ?string $api_key;

    public string $parcel_category;

    public string $payment_type;

    public bool $contract;

    public ?string $iban;

    public bool $insure;

    public bool $auto_register;

    public static function group(): string
    {
        return 'slovenskaposta';
    }

    /**
     * @return list<string>
     */
    public static function encrypted(): array
    {
        return ['api_key'];
    }
}
