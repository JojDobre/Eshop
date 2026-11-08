<?php

namespace Modules\SlovenskaPosta;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Modules\Shipping\Carriers\CarrierException;

/**
 * REST API elektronického podacieho hárku Slovenskej pošty (Web ePH API).
 *
 * Adresa https://mojezasielky.posta.sk/integration/rest/v1, hlavička
 * „x-api-auth: apikey {userId}:{apiKey}“. Odpoveď obsahuje „status“: „ok“,
 * inak zoznam „validation_errors“ s položkami attribute a reason.
 */
class SlovenskaPostaApi
{
    public const BASE_URL = 'https://mojezasielky.posta.sk/integration/rest/v1';

    public function __construct(private readonly SlovenskaPostaSettings $settings) {}

    /**
     * @param  array<string, mixed>|null  $body
     * @return array<string, mixed>
     *
     * @throws CarrierException
     */
    public function request(string $method, string $path, ?array $body = null): array
    {
        if (blank($this->settings->user_id) || blank($this->settings->api_key)) {
            throw new CarrierException(__('slovenskaposta::admin.missing_credentials'), CarrierException::CONFIGURATION);
        }

        try {
            $response = Http::timeout(60)->acceptJson()->asJson()
                ->withHeaders(['x-api-auth' => 'apikey '.$this->settings->user_id.':'.$this->settings->api_key])
                ->send($method, self::BASE_URL.$path, $body === null ? [] : ['json' => $body]);
        } catch (ConnectionException $e) {
            throw new CarrierException(__('slovenskaposta::admin.unavailable'), CarrierException::UNAVAILABLE, $e);
        }

        if (in_array($response->status(), [401, 403], true)) {
            throw new CarrierException(__('slovenskaposta::admin.unauthorized'), CarrierException::CONFIGURATION);
        }

        if ($response->serverError() || $response->status() === 429) {
            throw new CarrierException(__('slovenskaposta::admin.unavailable'), CarrierException::UNAVAILABLE);
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw $response->clientError()
                ? new CarrierException('Slovenská pošta: HTTP '.$response->status(), CarrierException::INVALID_DATA)
                : new CarrierException(__('slovenskaposta::admin.invalid_response'), CarrierException::UNAVAILABLE);
        }

        if (($json['status'] ?? null) !== 'ok') {
            $errors = collect($json['validation_errors'] ?? [])
                ->map(fn ($error) => trim(($error['attribute'] ?? '').' ('.($error['reason'] ?? '').')'))
                ->implode(', ');

            throw new CarrierException('Slovenská pošta: '.($errors ?: ($json['message'] ?? 'HTTP '.$response->status())), CarrierException::INVALID_DATA);
        }

        return $json;
    }

    /**
     * Stiahne PDF z adresy, ktorú API vráti pre štítky.
     *
     * @throws CarrierException
     */
    public function download(string $url): string
    {
        try {
            $response = Http::timeout(60)->get($url);
        } catch (ConnectionException $e) {
            throw new CarrierException(__('slovenskaposta::admin.unavailable'), CarrierException::UNAVAILABLE, $e);
        }

        if (! $response->successful() || ! str_starts_with($response->body(), '%PDF')) {
            throw new CarrierException(__('slovenskaposta::admin.invalid_response'), CarrierException::UNAVAILABLE);
        }

        return $response->body();
    }
}
