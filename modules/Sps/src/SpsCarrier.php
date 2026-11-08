<?php

namespace Modules\Sps;

use App\Settings\CompanySettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Modules\Shipping\Carriers\CarrierException;
use Modules\Shipping\Carriers\SeparateLabels;
use Modules\Shipping\Carriers\ShipmentRequest;
use Modules\Shipping\Carriers\ShipmentResult;
use Modules\Shipping\Carriers\ShipmentStatus;
use Modules\Shipping\Carriers\ShippingCarrier;
use Modules\Shipping\Carriers\TrackingResult;
use Modules\Shipping\Models\ShippingMethod;
use Modules\Shipping\ShippingType;

/**
 * SPS (Slovak Parcel Service) cez WebShip API: kuriér na adresu, dobierka, avízo príjemcovi,
 * štítok pri vytvorení zásielky a stav zásielky podľa čísla objednávky (referencie).
 */
class SpsCarrier implements SeparateLabels, ShippingCarrier
{
    public function __construct(private readonly SpsApi $api, private readonly SpsSettings $settings) {}

    public function key(): string
    {
        return 'sps';
    }

    public function label(): string
    {
        return 'SPS';
    }

    public function isConfigured(): bool
    {
        return filled($this->settings->username) && filled($this->settings->password);
    }

    public function supports(ShippingType $type): bool
    {
        // Doručenie na SPS ParcelShop API verejne nepopisuje – zatiaľ iba na adresu.
        return $type === ShippingType::Delivery;
    }

    public function pickupSelector(ShippingMethod $method): ?array
    {
        return null;
    }

    public function methodFields(): array
    {
        return [];
    }

    /**
     * Zásielka vo formáte WebServiceShipment.
     *
     * @return array<string, mixed>
     */
    public function shipment(ShipmentRequest $request): array
    {
        $cod = null;

        if ($request->codAmount !== null) {
            $iban = str_replace(' ', '', (string) ($this->settings->cod_iban ?: app(CompanySettings::class)->iban));
            $cod = array_filter([
                'codretbankacc' => $iban !== '' ? $iban : null,
                'codvalue' => number_format($request->codAmount / 100, 2, '.', ''),
                'currency' => $request->currency,
            ], fn ($value) => $value !== null);
        }

        return [
            'cod' => $cod,
            'deliveryaddress' => array_filter([
                'city' => (string) $request->city,
                'contactPerson' => $request->fullName(),
                'country' => strtoupper($request->country),
                'email' => $request->email,
                'mobile' => $request->phone,
                'name' => mb_substr($request->company ?: $request->fullName(), 0, 50),
                'phone' => $request->phone,
                'street' => (string) $request->street,
                'zip' => str_replace(' ', '', (string) $request->zip),
            ], fn ($value) => $value !== null && $value !== ''),
            'insurvalue' => $this->settings->insure ? number_format($request->value / 100, 2, '.', '') : null,
            'notifytype' => (string) $this->settings->notify,
            'packages' => [['reffnr' => $request->reference, 'weight' => number_format(max(0.1, $request->weightGrams / 1000), 2, '.', '')]],
            'productdesc' => $request->note ? mb_substr($request->note, 0, 50) : null,
            'recipientpay' => false,
            'returnshipment' => false,
            'saturdayshipment' => false,
            'servicename' => $this->settings->service ?: 'expres',
            'units' => 'kg',
            'deliverytype' => '2PT',
            // 0 = dobierka v hotovosti (kartou platí príjemca podľa možností kuriéra).
            'codattribute' => 0,
        ];
    }

    public function createShipment(ShipmentRequest $request): ShipmentResult
    {
        $result = $this->api->call('createAndPrintCifShipment', ['webServiceShipment' => $this->shipment($request)]);
        $errors = trim((string) ($result->result->errors ?? ''));

        if ($errors !== '') {
            throw new CarrierException('SPS: '.$errors, CarrierException::INVALID_DATA);
        }

        $number = trim((string) ($result->packageInfo->item[0]->shipNr ?? ''));

        if ($number === '') {
            throw new CarrierException(__('sps::admin.invalid_response'), CarrierException::UNAVAILABLE);
        }

        $url = trim((string) ($result->documentUrl ?? ''));

        // Referencia = číslo objednávky (podľa nej sa zisťuje stav), sledovacie číslo = číslo zásielky SPS.
        return new ShipmentResult($request->reference, $number, $url !== '' ? $this->download($url) : null);
    }

    private function download(string $url): ?string
    {
        try {
            $response = Http::timeout(60)->get($url);
        } catch (ConnectionException) {
            return null;
        }

        // Štítok sa dá vytlačiť aj neskôr z portálu WebShip, zásielka je už vytvorená.
        return $response->successful() && str_starts_with($response->body(), '%PDF') ? $response->body() : null;
    }

    public function labels(array $references): string
    {
        // WebShip vracia štítok pri vytvorení zásielky (uložený v obchode); znovu sa dá vytlačiť iba v portáli.
        throw new CarrierException(__('sps::admin.label_only_on_create'), CarrierException::INVALID_DATA);
    }

    /**
     * Stav podľa referencie balíka (číslo objednávky poslané pri vytvorení) – posledná udalosť.
     */
    public function track(string $reference, ?string $trackingNumber = null): ?TrackingResult
    {
        $result = $this->api->call('getShipmentStatusesByReference', ['reffnr' => $reference, 'langi' => app()->getLocale() === 'en' ? 'en' : 'sk']);
        $events = [];

        foreach ($result->events->item ?? [] as $event) {
            $events[] = $event;
        }

        if ($events === []) {
            return null;
        }

        usort($events, fn ($a, $b) => strcmp(self::moment($a), self::moment($b)));
        $last = end($events);
        $text = trim((string) $last->status) ?: null;

        return new TrackingResult(self::status((string) $text), $text);
    }

    private static function moment(\SimpleXMLElement $event): string
    {
        // Dátum „dd.mm.yyyy“ a čas „hh:mm“ → triediteľný reťazec.
        $date = (string) $event->date;

        return (preg_match('/(\d{1,2})\.(\d{1,2})\.(\d{4})/', $date, $m) ? sprintf('%04d%02d%02d', $m[3], $m[2], $m[1]) : $date).' '.$event->time;
    }

    /**
     * Stav zásielky podľa textu stavu SPS.
     */
    public static function status(string $text): ShipmentStatus
    {
        $text = mb_strtolower($text);

        return match (true) {
            str_contains($text, 'vrát') || str_contains($text, 'späť') || str_contains($text, 'return') => ShipmentStatus::Returned,
            str_contains($text, 'storn') || str_contains($text, 'zruš') || str_contains($text, 'cancel') => ShipmentStatus::Cancelled,
            str_contains($text, 'doručen') || str_contains($text, 'delivered') => ShipmentStatus::Delivered,
            str_contains($text, 'údaje') || str_contains($text, 'data') || str_contains($text, 'prijat') => ShipmentStatus::Created,
            default => ShipmentStatus::InTransit,
        };
    }

    public function trackingUrl(string $trackingNumber, array $methodOptions = []): ?string
    {
        return 'https://www.sps-sro.sk/moja-zasielka/?shipment_id='.rawurlencode($trackingNumber);
    }

    public function testConnection(): void
    {
        // Operácia stavu podľa referencie vyžaduje prihlásenie; nesprávne údaje vrátia chybu.
        $result = $this->api->call('getShipmentStatusesByReference', ['reffnr' => 'test-0', 'langi' => 'sk']);
        $errors = mb_strtolower(trim((string) ($result->errors ?? '')));

        if ($errors !== '' && (str_contains($errors, 'login') || str_contains($errors, 'heslo') || str_contains($errors, 'password') || str_contains($errors, 'user'))) {
            throw new CarrierException('SPS: '.$errors, CarrierException::CONFIGURATION);
        }
    }

    public function syncPickupPoints(): int
    {
        return 0;
    }
}
