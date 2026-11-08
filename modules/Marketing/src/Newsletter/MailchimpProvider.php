<?php

namespace Modules\Marketing\Newsletter;

use Illuminate\Support\Facades\Http;
use Modules\Marketing\Models\Subscriber;
use RuntimeException;

/**
 * Mailchimp Marketing API 3.0: PUT /lists/{list}/members/{md5(e-mail)} (pridá alebo aktualizuje).
 * Dátové centrum je časť kľúča za pomlčkou (napr. „…-us21“).
 */
class MailchimpProvider implements NewsletterProvider
{
    public function __construct(private readonly string $apiKey, private readonly string $listId) {}

    public function sync(Subscriber $subscriber): void
    {
        $dc = str_contains($this->apiKey, '-') ? substr($this->apiKey, strrpos($this->apiKey, '-') + 1) : 'us1';

        if (! preg_match('/^[a-z]+\d+$/', $dc)) {
            throw new RuntimeException(__('marketing::admin.newsletter.mailchimp_key_invalid'));
        }

        $status = $subscriber->status === Subscriber::SUBSCRIBED ? 'subscribed' : 'unsubscribed';
        $response = Http::timeout(20)->withBasicAuth('eshop', $this->apiKey)->asJson()
            ->put('https://'.$dc.'.api.mailchimp.com/3.0/lists/'.rawurlencode($this->listId).'/members/'.md5(strtolower($subscriber->email)), array_filter([
                'email_address' => $subscriber->email,
                'status_if_new' => $status,
                'status' => $status,
                'language' => $subscriber->locale,
                'merge_fields' => $subscriber->name ? ['FNAME' => $subscriber->name] : null,
            ]));

        if (! $response->successful()) {
            throw new RuntimeException('Mailchimp: '.($response->json('detail') ?? $response->json('title') ?? 'HTTP '.$response->status()));
        }
    }
}
