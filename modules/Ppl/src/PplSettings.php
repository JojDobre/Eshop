<?php

namespace Modules\Ppl;

use Spatie\LaravelSettings\Settings;

class PplSettings extends Settings
{
    public ?string $client_id;

    public ?string $client_secret;

    public bool $business;

    public string $label_page;

    public ?string $cod_iban;

    public static function group(): string
    {
        return 'ppl';
    }

    /**
     * @return list<string>
     */
    public static function encrypted(): array
    {
        return ['client_secret'];
    }
}
