<?php

namespace Modules\Gls;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Modules\Shipping\Carriers\CarrierException;

/**
 * MyGLS API (REST, formát JSON): https://api.mygls.{krajina}/{služba}.svc/json/{metóda}.
 * Každá požiadavka obsahuje Username a Password (SHA-512 heslo ako pole bajtov).
 * Dokumentácia: https://api.mygls.sk → MyGLS_API.pdf.
 */
class GlsApi
{
    public const COUNTRIES = ['sk', 'cz', 'hu', 'ro', 'si', 'hr', 'rs'];

    public function __construct(private readonly GlsSettings $settings) {}

    public function baseUrl(): string
    {
        $country = in_array($this->settings->country, self::COUNTRIES, true) ? $this->settings->country : 'sk';

        return 'https://api.'.($this->settings->test_mode ? 'test.' : '').'mygls.'.$country;
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     *
     * @throws CarrierException
     */
    public function call(string $service, string $method, array $body): array
    {
        if (blank($this->settings->username) || blank($this->settings->password)) {
            throw new CarrierException(__('gls::admin.missing_credentials'), CarrierException::CONFIGURATION);
        }

        $payload = [
            'Username' => $this->settings->username,
            'Password' => array_values(unpack('C*', hash('sha512', (string) $this->settings->password, true)) ?: []),
            ...$body,
        ];

        try {
            $response = Http::timeout(60)->acceptJson()->asJson()->post($this->baseUrl().'/'.$service.'.svc/json/'.$method, $payload);
        } catch (ConnectionException $e) {
            throw new CarrierException(__('gls::admin.unavailable'), CarrierException::UNAVAILABLE, $e);
        }

        if ($response->status() === 401) {
            throw new CarrierException(__('gls::admin.unauthorized'), CarrierException::CONFIGURATION);
        }

        if ($response->serverError() || $response->status() === 429) {
            throw new CarrierException(__('gls::admin.unavailable'), CarrierException::UNAVAILABLE);
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw new CarrierException(__('gls::admin.invalid_response'), CarrierException::UNAVAILABLE);
        }

        return $json;
    }

    /**
     * Chyby z odpovede (zoznamy ErrorInfo) ako výnimka.
     *
     * @param  array<int, array<string, mixed>>|null  $errors
     *
     * @throws CarrierException
     */
    public static function throwErrors(?array $errors): void
    {
        if (empty($errors)) {
            return;
        }

        $messages = array_map(fn (array $error) => trim(($error['ErrorCode'] ?? '').' '.($error['ErrorDescription'] ?? '')), $errors);

        throw new CarrierException('GLS: '.implode('; ', $messages), CarrierException::INVALID_DATA);
    }

    /**
     * PDF z poľa bajtov v odpovedi.
     *
     * @param  list<int>|string|null  $bytes
     */
    public static function bytes(array|string|null $bytes): string
    {
        if (is_string($bytes)) {
            return base64_decode($bytes, true) ?: '';
        }

        return implode('', array_map('chr', $bytes ?? []));
    }
}
