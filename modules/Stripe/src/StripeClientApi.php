<?php

namespace Modules\Stripe;

use Stripe\StripeClient;

/**
 * Stripe API cez oficiálnu knižnicu stripe/stripe-php.
 */
class StripeClientApi implements StripeApi
{
    public function createCheckoutSession(array $params): array
    {
        return $this->client()->checkout->sessions->create($params)->toArray();
    }

    public function retrieveCheckoutSession(string $id): array
    {
        return $this->client()->checkout->sessions->retrieve($id)->toArray();
    }

    public function verify(): string
    {
        $balance = $this->client()->balance->retrieve();

        return $balance->livemode ? 'live' : 'test';
    }

    private function client(): StripeClient
    {
        return new StripeClient([
            'api_key' => (string) app(StripeSettings::class)->secret_key,
            'max_network_retries' => 2,
        ]);
    }
}
