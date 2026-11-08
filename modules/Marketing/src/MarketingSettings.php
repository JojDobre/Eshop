<?php

namespace Modules\Marketing;

use Spatie\LaravelSettings\Settings;

class MarketingSettings extends Settings
{
    public bool $newsletter_enabled;

    public bool $newsletter_checkout;

    public string $newsletter_provider;

    public ?string $mailchimp_api_key;

    public ?string $mailchimp_list_id;

    public ?string $ecomail_api_key;

    public ?string $ecomail_list_id;

    public bool $abandoned_enabled;

    public int $abandoned_delay_hours;

    public bool $feeds_enabled;

    public bool $feeds_out_of_stock;

    public ?string $ga4_id;

    public ?string $google_ads_id;

    public ?string $google_ads_label;

    public ?string $meta_pixel_id;

    public static function group(): string
    {
        return 'marketing';
    }

    /**
     * @return list<string>
     */
    public static function encrypted(): array
    {
        return ['mailchimp_api_key', 'ecomail_api_key'];
    }

    /** Hodnota nastavenia, alebo predvolená, ak ešte nie je zmigrované. */
    public static function get(string $key, mixed $default = false): mixed
    {
        try {
            return app(self::class)->{$key};
        } catch (\Throwable) {
            return $default;
        }
    }
}
