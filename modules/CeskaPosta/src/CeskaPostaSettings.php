<?php

namespace Modules\CeskaPosta;

use Spatie\LaravelSettings\Settings;

class CeskaPostaSettings extends Settings
{
    public ?string $api_token;

    public ?string $secret_key;

    public ?string $customer_id;

    public ?string $post_code;

    public ?int $location_number;

    public ?string $contract_number;

    public bool $test_mode;

    public int $label_form;

    public ?string $parcel_size;

    public static function group(): string
    {
        return 'ceskaposta';
    }

    /**
     * @return list<string>
     */
    public static function encrypted(): array
    {
        return ['api_token', 'secret_key'];
    }
}
