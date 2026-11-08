<?php

namespace Modules\CeskaPosta;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Shipping\Carriers\CarrierException;

/**
 * B2B API Českej pošty – služba ZSKService (REST, JSON).
 *
 * Každá požiadavka je podpísaná: hlavičky Api-Token, Authorization-Timestamp,
 * Authorization-Content-SHA256 (SHA-256 tela) a Authorization s podpisom
 * HMAC-SHA256 reťazca „sha256Tela;časováPečiatka;nonce“ tajným kľúčom.
 * Dokumentácia: https://www.ceskaposta.cz/napi/b2b (B2B-ZSKService 1.14).
 */
class CeskaPostaApi
{
    public const PRODUCTION_URL = 'https://b2b.postaonline.cz:444/restservices/ZSKService/v1';

    public const TEST_URL = 'https://b2b-test.postaonline.cz:444/restservices/ZSKService/v1';

    public function __construct(private readonly CeskaPostaSettings $settings) {}

    public function baseUrl(): string
    {
        return $this->settings->test_mode ? self::TEST_URL : self::PRODUCTION_URL;
    }

    /**
     * Hlavičky podpisu pre dané telo požiadavky ('' pri GET).
     *
     * @return array<string, string>
     */
    public function signedHeaders(string $body, ?int $timestamp = null, ?string $nonce = null): array
    {
        $timestamp ??= time();
        $nonce ??= (string) Str::uuid();
        $contentHash = $body === '' ? '' : hash('sha256', $body);
        $signature = base64_encode(hash_hmac('sha256', $contentHash.';'.$timestamp.';'.$nonce, (string) $this->settings->secret_key, true));

        return array_filter([
            'Api-Token' => (string) $this->settings->api_token,
            'Authorization-Timestamp' => (string) $timestamp,
            'Authorization-Content-SHA256' => $contentHash,
            'Authorization' => 'CP-HMAC-SHA256 nonce="'.$nonce.'" signature="'.$signature.'"',
        ], fn (string $value) => $value !== '');
    }

    /**
     * @param  array<string, mixed>|null  $body
     * @return array<string, mixed>
     *
     * @throws CarrierException
     */
    public function request(string $method, string $path, ?array $body = null): array
    {
        if (blank($this->settings->api_token) || blank($this->settings->secret_key)) {
            throw new CarrierException(__('ceskaposta::admin.missing_credentials'), CarrierException::CONFIGURATION);
        }

        $json = $body === null ? '' : json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $query = filled($this->settings->contract_number) ? '?idContract='.rawurlencode((string) $this->settings->contract_number) : '';
        $client = Http::timeout(60)->acceptJson()->withHeaders($this->signedHeaders($json));

        try {
            $response = $body === null
                ? $client->get($this->baseUrl().$path.$query)
                : $client->withBody($json, 'application/json;charset=UTF-8')->post($this->baseUrl().$path.$query);
        } catch (ConnectionException $e) {
            throw new CarrierException(__('ceskaposta::admin.unavailable'), CarrierException::UNAVAILABLE, $e);
        }

        return $this->decode($response);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws CarrierException
     */
    private function decode(Response $response): array
    {
        if (in_array($response->status(), [401, 403], true)) {
            throw new CarrierException(__('ceskaposta::admin.unauthorized'), CarrierException::CONFIGURATION);
        }

        if ($response->serverError() || $response->status() === 429) {
            throw new CarrierException(__('ceskaposta::admin.unavailable'), CarrierException::UNAVAILABLE);
        }

        $json = $response->json();

        if ($response->clientError()) {
            // Chybová odpoveď: { errorCode, errorDescription } alebo zoznam chýb.
            $message = is_array($json) ? ($json['errorDescription'] ?? $json['errorText'] ?? $json['message'] ?? null) : null;

            throw new CarrierException('Česká pošta: '.($message ?: 'HTTP '.$response->status()), CarrierException::INVALID_DATA);
        }

        if (! is_array($json)) {
            throw new CarrierException(__('ceskaposta::admin.invalid_response'), CarrierException::UNAVAILABLE);
        }

        return $json;
    }

    /**
     * Chyby zo zoznamu stavov (responseCode 1 = OK; texty INFO/WARNING sú len upozornenia).
     *
     * @param  list<array{responseCode?: int, responseText?: string}>|null  $states
     *
     * @throws CarrierException
     */
    public static function throwErrors(?array $states): void
    {
        $errors = collect($states ?? [])
            ->filter(fn ($state) => (int) ($state['responseCode'] ?? 1) !== 1
                && ! preg_match('/^(INFO|WARN)/i', (string) ($state['responseText'] ?? '')))
            ->map(fn ($state) => trim(($state['responseCode'] ?? '').' '.($state['responseText'] ?? '')));

        if ($errors->isNotEmpty()) {
            throw new CarrierException('Česká pošta: '.$errors->implode(', '), CarrierException::INVALID_DATA);
        }
    }
}
