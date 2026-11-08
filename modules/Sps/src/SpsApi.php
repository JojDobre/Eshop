<?php

namespace Modules\Sps;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Modules\Shipping\Carriers\CarrierException;
use SimpleXMLElement;

/**
 * WebShip API SPS (SOAP 1.1, document/literal) na https://webship.sps-sro.sk/services/WebshipWebService.
 * Požiadavky sa skladajú priamo ako XML, aby nebolo treba rozšírenie PHP „soap“ (na hostingoch často chýba).
 */
class SpsApi
{
    public const URL = 'https://webship.sps-sro.sk/services/WebshipWebService';

    /** Menný priestor operácií a polí (server) a dátových typov (customerData). */
    private const NS_SERVER = 'http://server.webship.solver.sk';

    private const NS_DATA = 'http://customerData.webship.solver.sk';

    public function __construct(private readonly SpsSettings $settings) {}

    /**
     * Zavolá operáciu a vráti jej výsledok (<{operácia}Return>) bez menných priestorov.
     *
     * @param  array<string, mixed>  $parameters  parametre operácie (bez prihlasovacích údajov)
     * @param  array{0: string, 1: string}  $credentialNames  názvy polí s menom a heslom (líšia sa podľa operácie)
     *
     * @throws CarrierException
     */
    public function call(string $operation, array $parameters, array $credentialNames = ['name', 'password']): SimpleXMLElement
    {
        if (blank($this->settings->username) || blank($this->settings->password)) {
            throw new CarrierException(__('sps::admin.missing_credentials'), CarrierException::CONFIGURATION);
        }

        $body = self::element('s:'.$credentialNames[0], $this->settings->username)
            .self::element('s:'.$credentialNames[1], $this->settings->password)
            .self::serialize($parameters, 's');

        $envelope = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:s="'.self::NS_SERVER.'" xmlns:c="'.self::NS_DATA.'">'
            .'<soapenv:Body><s:'.$operation.'>'.$body.'</s:'.$operation.'></soapenv:Body></soapenv:Envelope>';

        try {
            $response = Http::timeout(60)->withHeaders(['SOAPAction' => '""'])
                ->withBody($envelope, 'text/xml; charset=utf-8')->post(self::URL);
        } catch (ConnectionException $e) {
            throw new CarrierException(__('sps::admin.unavailable'), CarrierException::UNAVAILABLE, $e);
        }

        $xml = self::parse($response->body());

        if ($xml === null) {
            throw new CarrierException(__('sps::admin.'.($response->serverError() ? 'unavailable' : 'invalid_response')), CarrierException::UNAVAILABLE);
        }

        if (($fault = $xml->xpath('//faultstring')) !== [] && $fault !== false) {
            throw new CarrierException('SPS: '.trim((string) $fault[0]), CarrierException::INVALID_DATA);
        }

        $result = $xml->xpath('//'.$operation.'Return');

        if ($result === [] || $result === false) {
            throw new CarrierException(__('sps::admin.invalid_response'), CarrierException::UNAVAILABLE);
        }

        return $result[0];
    }

    /**
     * Polia vo vnútri dátových typov patria do menného priestoru customerData, položky polí (item) do server.
     *
     * @param  array<string, mixed>  $data
     */
    public static function serialize(array $data, string $prefix = 'c'): string
    {
        $xml = '';

        foreach ($data as $name => $value) {
            if ($value === null) {
                continue;
            }

            if (is_array($value) && array_is_list($value)) {
                $items = '';

                foreach ($value as $item) {
                    $items .= '<s:item>'.(is_array($item) ? self::serialize($item) : htmlspecialchars((string) $item, ENT_XML1)).'</s:item>';
                }

                $xml .= '<'.$prefix.':'.$name.'>'.$items.'</'.$prefix.':'.$name.'>';
            } elseif (is_array($value)) {
                $xml .= '<'.$prefix.':'.$name.'>'.self::serialize($value).'</'.$prefix.':'.$name.'>';
            } else {
                $xml .= self::element($prefix.':'.$name, is_bool($value) ? ($value ? 'true' : 'false') : (string) $value);
            }
        }

        return $xml;
    }

    private static function element(string $name, string $value): string
    {
        return '<'.$name.'>'.htmlspecialchars($value, ENT_XML1).'</'.$name.'>';
    }

    /**
     * XML odpovede bez menných priestorov (jednoduchšie hľadanie cez xpath).
     */
    public static function parse(string $body): ?SimpleXMLElement
    {
        if (trim($body) === '') {
            return null;
        }

        $plain = preg_replace(['/\sxmlns(:\w+)?="[^"]*"/', '/(<\/?)[\w-]+:/', '/\s[\w-]+:(\w+=)/'], ['', '$1', ' $1'], $body);
        $xml = @simplexml_load_string((string) $plain, SimpleXMLElement::class, LIBXML_NONET);

        return $xml === false ? null : $xml;
    }
}
