<?php

namespace Modules\Marketing\Newsletter;

use Illuminate\Support\Facades\Http;
use Modules\Marketing\Models\Subscriber;
use RuntimeException;

/**
 * Ecomail API v2: prihlásenie POST /lists/{list}/subscribe, odhlásenie DELETE /lists/{list}/unsubscribe.
 */
class EcomailProvider implements NewsletterProvider
{
    public const BASE_URL = 'https://api2.ecomailapp.cz';

    public function __construct(private readonly string $apiKey, private readonly string $listId) {}

    public function sync(Subscriber $subscriber): void
    {
        $client = Http::timeout(20)->withHeaders(['key' => $this->apiKey])->asJson()->acceptJson();
        $url = self::BASE_URL.'/lists/'.rawurlencode($this->listId);

        $response = $subscriber->status === Subscriber::SUBSCRIBED
            ? $client->post($url.'/subscribe', [
                'subscriber_data' => array_filter(['email' => $subscriber->email, 'name' => $subscriber->name]),
                'update_existing' => true,
                'resubscribe' => true,
                'trigger_autoresponders' => true,
                // Súhlas je už potvrdený v obchode (double opt-in).
                'skip_confirmation' => true,
            ])
            : $client->delete($url.'/unsubscribe', ['email' => $subscriber->email]);

        if (! $response->successful()) {
            throw new RuntimeException('Ecomail: '.($response->json('message') ?? 'HTTP '.$response->status()));
        }
    }
}
