<?php

namespace Modules\Balikobot;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Modules\Shipping\Carriers\CarrierException;

/**
 * Balíkobot API v2 (https://apiv2.balikobot.cz/{kuriér}/{metóda}), prihlásenie Basic (API používateľ a kľúč).
 * Odpoveď obsahuje „status“ (200 = v poriadku); chyby zásielok sú v packages[].errors.
 */
class BalikobotApi
{
    public const BASE_URL = 'https://apiv2.balikobot.cz';

    public function __construct(private readonly BalikobotSettings $settings) {}

    /**
     * @param  array<string, mixed>|null  $body  null = GET
     * @return array<string, mixed>
     *
     * @throws CarrierException
     */
    public function call(string $path, ?array $body = null): array
    {
        if (blank($this->settings->api_user) || blank($this->settings->api_key)) {
            throw new CarrierException(__('balikobot::admin.missing_credentials'), CarrierException::CONFIGURATION);
        }

        $client = Http::timeout(120)->acceptJson()->withBasicAuth((string) $this->settings->api_user, (string) $this->settings->api_key);

        try {
            $response = $body === null ? $client->get(self::BASE_URL.'/'.$path) : $client->asJson()->post(self::BASE_URL.'/'.$path, $body);
        } catch (ConnectionException $e) {
            throw new CarrierException(__('balikobot::admin.unavailable'), CarrierException::UNAVAILABLE, $e);
        }

        $json = $response->json();
        $status = is_array($json) ? (int) ($json['status'] ?? $response->status()) : $response->status();

        if ($response->status() === 401 || $status === 401 || $status === 403) {
            throw new CarrierException(__('balikobot::admin.unauthorized'), CarrierException::CONFIGURATION);
        }

        if ($response->serverError() || $status >= 500 || $status === 429) {
            throw new CarrierException(__('balikobot::admin.unavailable'), CarrierException::UNAVAILABLE);
        }

        if (! is_array($json)) {
            throw new CarrierException(__('balikobot::admin.invalid_response'), CarrierException::UNAVAILABLE);
        }

        if ($status >= 400 || $response->clientError()) {
            throw new CarrierException('Balíkobot: '.self::errors($json), CarrierException::INVALID_DATA);
        }

        return $json;
    }

    /**
     * Chybové hlásenia z odpovede (packages[].errors, status_message).
     *
     * @param  array<string, mixed>  $json
     */
    public static function errors(array $json): string
    {
        $messages = [];

        foreach ((array) ($json['packages'] ?? []) as $package) {
            foreach ((array) ($package['errors'] ?? []) as $field => $error) {
                $messages[] = (is_string($field) ? $field.': ' : '').(is_array($error) ? ($error['message'] ?? implode(', ', array_filter($error, 'is_string'))) : $error);
            }

            if (isset($package['status_message'])) {
                $messages[] = $package['status_message'];
            }
        }

        if ($messages === [] && isset($json['status_message'])) {
            $messages[] = $json['status_message'];
        }

        return $messages === [] ? 'HTTP '.($json['status'] ?? '400') : implode('; ', array_unique(array_map('strval', $messages)));
    }
}
