<?php

namespace Modules\SlovenskaPosta;

use App\Settings\CompanySettings;
use App\Settings\GeneralSettings;
use Modules\Shipping\Carriers\CarrierException;
use Modules\Shipping\Carriers\SeparateLabels;
use Modules\Shipping\Carriers\ShipmentRequest;
use Modules\Shipping\Carriers\ShipmentResult;
use Modules\Shipping\Carriers\ShippingCarrier;
use Modules\Shipping\Carriers\TrackingResult;
use Modules\Shipping\Models\ShippingMethod;
use Modules\Shipping\ShippingType;

/**
 * Slovenská pošta cez Web ePH API: každá zásielka dostane vlastný podací hárok
 * (vytvorenie hárku → pridanie zásielky → štítok → odoslanie hárku pošte).
 * Referencia zásielky má tvar „idHárku/idZásielky“.
 */
class SlovenskaPostaCarrier implements SeparateLabels, ShippingCarrier
{
    public function __construct(private readonly SlovenskaPostaApi $api, private readonly SlovenskaPostaSettings $settings) {}

    public function key(): string
    {
        return 'slovenskaposta';
    }

    public function label(): string
    {
        return __('slovenskaposta::admin.carrier');
    }

    public function isConfigured(): bool
    {
        return filled($this->settings->user_id) && filled($this->settings->api_key);
    }

    public function supports(ShippingType $type): bool
    {
        // Výdajné miesta (BalíkoBOX, pošta) API ePH verejne nepopisuje – zatiaľ iba doručenie na adresu.
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

    public function createShipment(ShipmentRequest $request): ShipmentResult
    {
        $sheet = $this->api->request('PUT', '/sheets', ['sheet' => $this->sheet()]);
        $sheetId = $sheet['sheet']['id'] ?? null;

        if (blank($sheetId)) {
            throw new CarrierException(__('slovenskaposta::admin.invalid_response'), CarrierException::UNAVAILABLE);
        }

        $parcel = $this->api->request('PUT', '/sheets/'.$sheetId.'/parcels', ['parcel' => $this->parcel($request)]);
        $parcelId = $parcel['parcel']['id'] ?? null;

        if (blank($parcelId)) {
            throw new CarrierException(__('slovenskaposta::admin.invalid_response'), CarrierException::UNAVAILABLE);
        }

        $reference = $sheetId.'/'.$parcelId;
        $label = $this->labels([$reference]);

        if ($this->settings->auto_register) {
            $this->api->request('POST', '/sheets/'.$sheetId.'/register', []);
        }

        return new ShipmentResult($reference, filled($parcel['parcel']['parcel_number'] ?? null) ? (string) $parcel['parcel']['parcel_number'] : null, $label);
    }

    /**
     * Hlavička podacieho hárku s odosielateľom z Nastavenia → Firma.
     *
     * @return array<string, mixed>
     */
    public function sheet(): array
    {
        $company = app(CompanySettings::class);
        $shop = app(GeneralSettings::class);

        return [
            'parcel_category' => $this->settings->parcel_category,
            'payment_type' => $this->settings->payment_type,
            'reception_method' => 'post',
            'contract' => $this->settings->contract,
            'sender' => array_filter([
                'name' => (string) ($company->company_name ?: $shop->shop_name),
                'organization' => $company->company_name,
                'street' => (string) $company->street,
                'city' => (string) $company->city,
                'zip' => str_replace(' ', '', (string) $company->zip),
                'country' => strtoupper((string) ($company->country ?: 'SK')),
                'phone' => $shop->contact_phone,
                'email' => $shop->contact_email,
            ], fn ($value) => $value !== null && $value !== ''),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function parcel(ShipmentRequest $request): array
    {
        $parcel = [
            // Identifikátor a poznámka smú mať najviac 10 znakov.
            'id' => mb_substr($request->reference, -10),
            'recipient' => array_filter([
                'name' => $request->fullName(),
                'organization' => $request->company,
                'street' => (string) $request->street,
                'city' => (string) $request->city,
                'zip' => str_replace(' ', '', (string) $request->zip),
                'country' => strtoupper($request->country),
                'phone' => $request->phone,
                'email' => $request->email,
            ], fn ($value) => $value !== null && $value !== ''),
            'weight' => round(max(0.1, $request->weightGrams / 1000), 3),
            'note' => mb_substr($request->reference, -10),
        ];

        if ($request->codAmount !== null) {
            $iban = str_replace(' ', '', (string) ($this->settings->iban ?: app(CompanySettings::class)->iban));

            if ($iban === '') {
                throw new CarrierException(__('slovenskaposta::admin.missing_iban'), CarrierException::CONFIGURATION);
            }

            // bdnu = bezhotovostná dobierka poukázaná na účet.
            $parcel['cod'] = [
                'type' => 'bdnu',
                'amount' => ['value' => round($request->codAmount / 100, 2), 'currency' => $request->currency],
                'iban' => $iban,
                'symbol' => substr(preg_replace('/\D/', '', $request->reference) ?: '0', -10),
            ];
        }

        if ($this->settings->insure) {
            $parcel['insurance'] = ['value' => round($request->value / 100, 2), 'currency' => $request->currency];
        }

        return $parcel;
    }

    public function labels(array $references): string
    {
        // API vracia štítok vždy pre jednu zásielku (preto rozhranie SeparateLabels).
        [$sheetId, $parcelId] = array_pad(explode('/', (string) ($references[0] ?? ''), 2), 2, '');

        if ($sheetId === '' || $parcelId === '') {
            throw new CarrierException(__('slovenskaposta::admin.invalid_response'), CarrierException::INVALID_DATA);
        }

        $response = $this->api->request('POST', '/sheets/'.$sheetId.'/parcels/'.$parcelId.'/labels', ['format' => 'pdf']);
        $url = $response['labels']['url'] ?? null;

        if (blank($url)) {
            throw new CarrierException(__('slovenskaposta::admin.invalid_response'), CarrierException::UNAVAILABLE);
        }

        return $this->api->download((string) $url);
    }

    public function track(string $reference, ?string $trackingNumber = null): ?TrackingResult
    {
        // Stav zásielky API ePH neposkytuje; zákazník dostane odkaz na sledovanie zásielok pošty.
        return null;
    }

    public function trackingUrl(string $trackingNumber, array $methodOptions = []): ?string
    {
        return 'https://tandt.posta.sk/zasielky/'.rawurlencode($trackingNumber);
    }

    public function testConnection(): void
    {
        // Server overuje prihlásenie pred čímkoľvek iným: nesprávne údaje vrátia 401 (výnimka CONFIGURATION),
        // správne údaje odpoveď alebo chybu požiadavky. Nič sa pritom nevytvára.
        try {
            $this->api->request('GET', '/sheets');
        } catch (CarrierException $e) {
            if ($e->type !== CarrierException::INVALID_DATA) {
                throw $e;
            }
        }
    }

    public function syncPickupPoints(): int
    {
        return 0;
    }
}
