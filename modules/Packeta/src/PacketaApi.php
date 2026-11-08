<?php

namespace Modules\Packeta;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Modules\Shipping\Carriers\CarrierException;
use SimpleXMLElement;

/**
 * REST/XML rozhranie Packeta: POST s XML telom, koreňový element = názov metódy.
 * Odpoveď: <response><status>ok|fault</status><result>…</result> alebo <fault>, <string>, <detail>.
 * Dokumentácia: https://docs.packeta.com (API reference).
 */
class PacketaApi
{
    public const ENDPOINT = 'https://www.zasilkovna.cz/api/rest';

    public function __construct(private readonly PacketaSettings $settings) {}

    /**
     * @param  array<string, mixed>  $arguments
     *
     * @throws CarrierException
     */
    public function call(string $method, array $arguments): SimpleXMLElement
    {
        $password = (string) $this->settings->api_password;

        if ($password === '') {
            throw new CarrierException(__('packeta::admin.missing_password'), CarrierException::CONFIGURATION);
        }

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><'.$method.'/>');
        $xml->addChild('apiPassword', htmlspecialchars($password, ENT_XML1));
        self::append($xml, $arguments);

        try {
            $response = Http::timeout(30)->withBody((string) $xml->asXML(), 'text/xml; charset=utf-8')->post(self::ENDPOINT);
        } catch (ConnectionException $e) {
            throw new CarrierException(__('packeta::admin.unavailable'), CarrierException::UNAVAILABLE, $e);
        }

        if ($response->serverError() || $response->status() === 429) {
            throw new CarrierException(__('packeta::admin.unavailable'), CarrierException::UNAVAILABLE);
        }

        $body = @simplexml_load_string($response->body());

        if ($body === false) {
            throw new CarrierException(__('packeta::admin.invalid_response'), CarrierException::UNAVAILABLE);
        }

        if ((string) $body->status !== 'ok') {
            throw self::fault($body);
        }

        return $body->result;
    }

    /**
     * Výnimka zo správy o chybe (vrátane chýb jednotlivých polí zásielky).
     */
    private static function fault(SimpleXMLElement $body): CarrierException
    {
        $fault = (string) $body->fault;
        $message = trim((string) $body->string) ?: $fault;
        $details = [];

        foreach ($body->xpath('//detail//fault') ?: [] as $attribute) {
            if (isset($attribute->name)) {
                $details[] = trim((string) $attribute->name).': '.trim((string) $attribute->fault);
            }
        }

        if ($details !== []) {
            $message .= ' ('.implode('; ', $details).')';
        }

        $type = match ($fault) {
            'IncorrectApiPasswordFault' => CarrierException::CONFIGURATION,
            'ExternalGatewayFault' => CarrierException::UNAVAILABLE,
            default => CarrierException::INVALID_DATA,
        };

        return new CarrierException('Packeta: '.$message, $type);
    }

    /**
     * @param  array<string|int, mixed>  $values
     */
    private static function append(SimpleXMLElement $node, array $values, ?string $listItem = null): void
    {
        foreach ($values as $key => $value) {
            if ($value === null) {
                continue;
            }

            $name = is_int($key) ? (string) $listItem : $key;

            if (is_array($value)) {
                // Zoznam (napr. packetIds) má položky s názvom podľa kľúča „_item“.
                $item = $value['_item'] ?? null;
                unset($value['_item']);
                self::append($node->addChild($name), $value, $item);

                continue;
            }

            $node->addChild($name, htmlspecialchars(is_bool($value) ? ($value ? '1' : '0') : (string) $value, ENT_XML1));
        }
    }
}
