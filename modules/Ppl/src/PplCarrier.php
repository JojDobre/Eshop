<?php

namespace Modules\Ppl;

use App\Settings\CompanySettings;
use App\Settings\GeneralSettings;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Illuminate\Support\Sleep;
use Modules\Shipping\Carriers\CarrierException;
use Modules\Shipping\Carriers\ShipmentRequest;
use Modules\Shipping\Carriers\ShipmentResult;
use Modules\Shipping\Carriers\ShipmentStatus;
use Modules\Shipping\Carriers\ShippingCarrier;
use Modules\Shipping\Carriers\TrackingResult;
use Modules\Shipping\Models\PickupPoint;
use Modules\Shipping\Models\ShippingMethod;
use Modules\Shipping\ShippingType;

/**
 * PPL cez myAPI2: PPL Parcel na adresu (CZ, Connect do EÚ), ParcelShopy a ParcelBoxy, dobierka, štítky, sledovanie.
 *
 * Zásielky sa zakladajú dávkou (asynchrónne): referencia zásielky je ID dávky, z ktorej sa neskôr
 * zistí číslo zásielky a štítok.
 */
class PplCarrier implements ShippingCarrier
{
    /** Koľkokrát (po sekunde) sa čaká na spracovanie dávky pri vytvorení zásielky. */
    public const BATCH_ATTEMPTS = 8;

    /** Krajiny výdajných miest PPL. */
    public const COUNTRIES = ['CZ', 'SK', 'PL', 'DE'];

    public function __construct(private readonly PplApi $api, private readonly PplSettings $settings) {}

    public function key(): string
    {
        return 'ppl';
    }

    public function label(): string
    {
        return 'PPL';
    }

    public function isConfigured(): bool
    {
        return filled($this->settings->client_id) && filled($this->settings->client_secret);
    }

    public function supports(ShippingType $type): bool
    {
        return in_array($type, [ShippingType::Delivery, ShippingType::PickupPoint], true);
    }

    public function pickupSelector(ShippingMethod $method): ?array
    {
        // ParcelShopy a ParcelBoxy sú uložené v obchode (denná aktualizácia), zákazník ich prehľadáva.
        return ['list' => true];
    }

    public function methodFields(): array
    {
        $t = fn (string $key) => __('ppl::admin.'.$key);

        return [
            Select::make('country')->label($t('country'))->selectablePlaceholder(false)->default('CZ')
                ->options(array_combine(self::COUNTRIES, self::COUNTRIES)),
            CheckboxList::make('point_types')->label($t('point_types'))
                ->options(['parcelshop' => $t('types.parcelshop'), 'parcelbox' => $t('types.parcelbox')])
                ->default(['parcelshop', 'parcelbox']),
        ];
    }

    /**
     * Produkt PPL podľa spôsobu doručenia, krajiny a dobierky.
     */
    public function productType(ShipmentRequest $request): string
    {
        $cod = $request->codAmount !== null;

        return match (true) {
            $request->pickupPoint !== null => $cod ? 'SMAD' : 'SMAR',
            strtoupper($request->country) !== 'CZ' => $cod ? 'COND' : 'CONN',
            $this->settings->business => $cod ? 'BUSD' : 'BUSS',
            default => $cod ? 'PRID' : 'PRIV',
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function shipment(ShipmentRequest $request): array
    {
        $company = app(CompanySettings::class);
        $shop = app(GeneralSettings::class);

        $shipment = [
            'referenceId' => $request->reference,
            'productType' => $this->productType($request),
            'note' => $request->note ? mb_substr($request->note, 0, 300) : null,
            'shipmentSet' => ['numberOfShipments' => 1],
            'sender' => array_filter([
                'name' => mb_substr((string) ($company->company_name ?: $shop->shop_name), 0, 50),
                'street' => (string) $company->street,
                'city' => (string) $company->city,
                'zipCode' => str_replace(' ', '', (string) $company->zip),
                'country' => strtoupper((string) ($company->country ?: 'CZ')),
                'phone' => $shop->contact_phone,
                'email' => $shop->contact_email,
            ], fn ($value) => $value !== null && $value !== ''),
            'recipient' => array_filter([
                'name' => mb_substr($request->company ?: $request->fullName(), 0, 50),
                'name2' => $request->company ? mb_substr($request->fullName(), 0, 50) : null,
                'street' => (string) $request->street,
                'city' => (string) $request->city,
                'zipCode' => str_replace(' ', '', (string) $request->zip),
                'country' => strtoupper($request->country),
                'contact' => $request->fullName(),
                'phone' => $request->phone,
                'email' => $request->email,
            ], fn ($value) => $value !== null && $value !== ''),
        ];

        if ($request->pickupPoint !== null) {
            $shipment['specificDelivery'] = ['parcelShopCode' => (string) $request->pickupPoint['code']];
        }

        if ($request->codAmount !== null) {
            $iban = str_replace(' ', '', (string) ($this->settings->cod_iban ?: app(CompanySettings::class)->iban));

            $shipment['cashOnDelivery'] = array_filter([
                'codPrice' => round($request->codAmount / 100, 2),
                'codCurrency' => $request->currency,
                // Variabilný symbol: iba číslice, najviac 10.
                'codVarSym' => substr(preg_replace('/\D/', '', $request->reference) ?: '0', -10),
                'iban' => $iban !== '' ? $iban : null,
            ], fn ($value) => $value !== null);
        }

        return array_filter($shipment, fn ($value) => $value !== null);
    }

    public function createShipment(ShipmentRequest $request): ShipmentResult
    {
        $response = $this->api->send('POST', '/shipment/batch', body: [
            'labelSettings' => $this->labelSettings(),
            'shipments' => [$this->shipment($request)],
        ]);

        // ID dávky je posledná časť hlavičky Location (…/shipment/batch/{id}).
        $location = (string) $response->header('Location');
        $batchId = basename(parse_url($location, PHP_URL_PATH) ?: '');

        if ($batchId === '') {
            throw new CarrierException(__('ppl::admin.invalid_response'), CarrierException::UNAVAILABLE);
        }

        $item = null;

        for ($attempt = 0; $attempt < self::BATCH_ATTEMPTS; $attempt++) {
            $item = $this->batchItem($batchId);

            if ($item !== null && in_array($item['importState'] ?? null, ['Complete', 'Error'], true)) {
                break;
            }

            Sleep::for(1)->second();
        }

        if (($item['importState'] ?? null) === 'Error') {
            throw new CarrierException('PPL: '.($item['errorMessage'] ?? $item['errorCode'] ?? __('ppl::admin.invalid_response')), CarrierException::INVALID_DATA);
        }

        // Dávka sa ešte spracúva: číslo zásielky a štítok sa doplnia pri sledovaní alebo tlači.
        if (($item['importState'] ?? null) !== 'Complete') {
            return new ShipmentResult($batchId, null, null);
        }

        $label = filled($item['labelUrl'] ?? null) ? $this->download((string) $item['labelUrl']) : null;

        return new ShipmentResult($batchId, filled($item['shipmentNumber'] ?? null) ? (string) $item['shipmentNumber'] : null, $label);
    }

    /**
     * @return array<string, mixed>
     */
    private function labelSettings(): array
    {
        return [
            'format' => 'Pdf',
            'completeLabelSettings' => [
                'isCompleteLabelRequested' => true,
                'pageSize' => $this->settings->label_page === 'A4' ? 'A4' : 'Default',
                'position' => 1,
            ],
        ];
    }

    /**
     * Prvá zásielka dávky: {referenceId, shipmentNumber, labelUrl, importState, errorMessage}.
     *
     * @return array<string, mixed>|null
     */
    private function batchItem(string $batchId): ?array
    {
        $json = $this->api->send('GET', '/shipment/batch/'.rawurlencode($batchId))->json();
        $item = $json['items'][0] ?? null;

        return is_array($item) ? $item : null;
    }

    private function download(string $url): string
    {
        $body = $this->api->send('GET', $url)->body();

        if (! str_starts_with($body, '%PDF')) {
            throw new CarrierException(__('ppl::admin.invalid_response'), CarrierException::UNAVAILABLE);
        }

        return $body;
    }

    public function labels(array $references): string
    {
        // Štítky viacerých dávok sa nedajú spojiť – pri jednej dávke celý hárok, inak prvá dávka.
        $batchId = (string) ($references[0] ?? '');
        $json = $this->api->send('GET', '/shipment/batch/'.rawurlencode($batchId))->json();
        $url = $json['completeLabel']['labelUrls'][0] ?? $json['items'][0]['labelUrl'] ?? null;

        if (blank($url)) {
            throw new CarrierException(__('ppl::admin.label_not_ready'), CarrierException::UNAVAILABLE);
        }

        return $this->download((string) $url);
    }

    public function track(string $reference, ?string $trackingNumber = null): ?TrackingResult
    {
        $number = $trackingNumber ?: ($this->batchItem($reference)['shipmentNumber'] ?? null);

        if (blank($number)) {
            return null;
        }

        $shipments = $this->api->send('GET', '/shipment', ['ShipmentNumbers' => $number, 'Limit' => 1, 'Offset' => 0])->json();
        $events = collect($shipments[0]['trackAndTrace']['events'] ?? [])->sortBy('eventDate')->values();
        $last = $events->last();

        if (! is_array($last)) {
            return null;
        }

        return new TrackingResult(self::status((string) ($last['phase'] ?? '')), trim((string) ($last['name'] ?? '')) ?: null);
    }

    /**
     * Fáza zásielky PPL (číselník shipmentPhase) → stav zásielky v obchode.
     */
    public static function status(string $phase): ShipmentStatus
    {
        return match ($phase) {
            'Order' => ShipmentStatus::Created,
            'PickupPoint' => ShipmentStatus::ReadyForPickup,
            'Delivered' => ShipmentStatus::Delivered,
            'BackToSender' => ShipmentStatus::Returned,
            'Canceled', 'Deleted' => ShipmentStatus::Cancelled,
            default => ShipmentStatus::InTransit,
        };
    }

    public function trackingUrl(string $trackingNumber, array $methodOptions = []): ?string
    {
        return 'https://www.ppl.cz/vyhledat-zasilku?shipmentId='.rawurlencode($trackingNumber);
    }

    public function testConnection(): void
    {
        // Nesprávne údaje zlyhajú už pri získaní tokenu (výnimka CONFIGURATION).
        $this->api->send('GET', '/info');
    }

    public function syncPickupPoints(): int
    {
        $countries = ShippingMethod::query()->where('carrier', 'ppl')->get()->toBase()
            ->map(fn (ShippingMethod $method) => strtoupper((string) ($method->carrier_options['country'] ?? 'CZ')))
            ->push('CZ')->unique()->values();

        return PickupPoint::syncCarrier('ppl', $this->accessPoints($countries->all()));
    }

    /**
     * Výdajné miesta po stránkach (najviac 1000 na požiadavku).
     *
     * @param  list<string>  $countries
     * @return \Generator<array<string, mixed>>
     */
    private function accessPoints(array $countries): \Generator
    {
        foreach ($countries as $country) {
            for ($offset = 0; ; $offset += 1000) {
                $points = $this->api->send('GET', '/accessPoint', ['Limit' => 1000, 'Offset' => $offset, 'CountryCode' => $country])->json();

                foreach ((array) $points as $point) {
                    if (! is_array($point) || blank($point['accessPointCode'] ?? null)) {
                        continue;
                    }

                    yield [
                        'code' => (string) $point['accessPointCode'],
                        'name' => trim(($point['name'] ?? '').' '.($point['name2'] ?? '')) ?: (string) $point['accessPointCode'],
                        'street' => $point['street'] ?? null,
                        'city' => $point['city'] ?? null,
                        'zip' => $point['zipCode'] ?? null,
                        'country' => (string) ($point['country'] ?? $country),
                        'type' => str_contains(strtolower((string) ($point['accessPointType'] ?? '')), 'box') ? 'parcelbox' : 'parcelshop',
                        'latitude' => $point['gps']['latitude'] ?? null,
                        'longitude' => $point['gps']['longitude'] ?? null,
                        'opening_hours' => self::openingHours($point['workHours'] ?? []),
                    ];
                }

                if (count((array) $points) < 1000) {
                    break;
                }
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $hours  [{weekDay, dayPart, openFrom, openTo}]
     */
    private static function openingHours(array $hours): ?string
    {
        $days = [1 => 'Po', 2 => 'Ut', 3 => 'St', 4 => 'Št', 5 => 'Pi', 6 => 'So', 7 => 'Ne', 0 => 'Ne'];
        $byDay = [];

        foreach ($hours as $row) {
            $day = $row['weekDay'] ?? null;

            if (isset($days[$day]) && filled($row['openFrom'] ?? null) && filled($row['openTo'] ?? null)) {
                $byDay[$days[$day]][] = substr((string) $row['openFrom'], 0, 5).'-'.substr((string) $row['openTo'], 0, 5);
            }
        }

        return $byDay === [] ? null : collect($byDay)->map(fn ($ranges, $day) => $day.' '.implode(', ', $ranges))->implode(', ');
    }
}
