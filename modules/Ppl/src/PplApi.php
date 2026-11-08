<?php

namespace Modules\Ppl;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Shipping\Carriers\CarrierException;

/**
 * Oficiálne API PPL myAPI2 (REST, JSON) na https://api.dhl.com/ecs/ppl/myapi2.
 * Prihlásenie OAuth2 (client_credentials) – prístupový token sa drží v cache do jeho vypršania.
 */
class PplApi
{
    public const BASE_URL = 'https://api.dhl.com/ecs/ppl/myapi2';

    public const TOKEN_URL = 'https://api.dhl.com/ecs/ppl/myapi2/login/getAccessToken';

    public function __construct(private readonly PplSettings $settings) {}

    /**
     * @throws CarrierException
     */
    public function token(): string
    {
        if (blank($this->settings->client_id) || blank($this->settings->client_secret)) {
            throw new CarrierException(__('ppl::admin.missing_credentials'), CarrierException::CONFIGURATION);
        }

        $key = 'ppl.token.'.sha1($this->settings->client_id.'|'.$this->settings->client_secret);

        if (is_string($token = Cache::get($key))) {
            return $token;
        }

        try {
            $response = Http::timeout(30)->asForm()->acceptJson()->post(self::TOKEN_URL, [
                'grant_type' => 'client_credentials',
                'client_id' => $this->settings->client_id,
                'client_secret' => $this->settings->client_secret,
            ]);
        } catch (ConnectionException $e) {
            throw new CarrierException(__('ppl::admin.unavailable'), CarrierException::UNAVAILABLE, $e);
        }

        if ($response->serverError()) {
            throw new CarrierException(__('ppl::admin.unavailable'), CarrierException::UNAVAILABLE);
        }

        $token = $response->json('access_token');

        if (! $response->successful() || ! is_string($token) || $token === '') {
            throw new CarrierException(__('ppl::admin.unauthorized'), CarrierException::CONFIGURATION);
        }

        // Token platí spravidla 30 minút; v cache sa drží o minútu kratšie.
        Cache::put($key, $token, max(60, (int) $response->json('expires_in', 1800) - 60));

        return $token;
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>|null  $body
     *
     * @throws CarrierException
     */
    public function send(string $method, string $path, array $query = [], ?array $body = null): Response
    {
        $url = str_starts_with($path, 'http') ? $path : self::BASE_URL.$path;
        $client = Http::timeout(60)->acceptJson()->withToken($this->token())->withHeaders(['Accept-Language' => 'cs-CZ']);

        try {
            $response = $body === null
                ? $client->send($method, $url, ['query' => $query])
                : $client->asJson()->send($method, $url, ['query' => $query, 'json' => $body]);
        } catch (ConnectionException $e) {
            throw new CarrierException(__('ppl::admin.unavailable'), CarrierException::UNAVAILABLE, $e);
        }

        if (in_array($response->status(), [401, 403], true)) {
            throw new CarrierException(__('ppl::admin.unauthorized'), CarrierException::CONFIGURATION);
        }

        if ($response->serverError() || $response->status() === 429) {
            throw new CarrierException(__('ppl::admin.unavailable'), CarrierException::UNAVAILABLE);
        }

        if ($response->clientError()) {
            throw new CarrierException('PPL: '.self::problem($response), CarrierException::INVALID_DATA);
        }

        return $response;
    }

    /**
     * Text chyby z odpovede „problem+json“ ({title, detail, errors: {pole: [správy]}}).
     */
    public static function problem(Response $response): string
    {
        $json = $response->json();

        if (! is_array($json)) {
            return 'HTTP '.$response->status();
        }

        $errors = collect($json['errors'] ?? [])
            ->map(fn ($messages, $field) => preg_replace('/^shipments\[\d+\]\./i', '', (string) $field).': '.implode(', ', (array) $messages))
            ->values()->implode('; ');

        return $errors !== '' ? $errors : (string) ($json['detail'] ?? $json['title'] ?? 'HTTP '.$response->status());
    }
}
