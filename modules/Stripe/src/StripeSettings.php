<?php

namespace Modules\Stripe;

use Spatie\LaravelSettings\Settings;

class StripeSettings extends Settings
{
    public bool $enabled;

    public ?string $publishable_key;

    public ?string $secret_key;

    public ?string $webhook_secret;

    public static function group(): string
    {
        return 'stripe';
    }

    /**
     * @return list<string>
     */
    public static function encrypted(): array
    {
        return ['secret_key', 'webhook_secret'];
    }

    public function isConfigured(): bool
    {
        return $this->enabled && filled($this->secret_key);
    }

    public function isTestMode(): bool
    {
        return str_contains((string) $this->secret_key, '_test_');
    }
}
