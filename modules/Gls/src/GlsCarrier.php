<?php

namespace Modules\Gls;

use App\Settings\CompanySettings;
use App\Settings\GeneralSettings;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Illuminate\Support\Carbon;
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
 * GLS cez MyGLS API: doručenie domov, ParcelShopy a GLS boxy (služba PSD), štítky, sledovanie, dobierka.
 */
class GlsCarrier implements ShippingCarrier
{
    /** Typy výdajných miest MyGLS (DeliveryPointType). */
    private const POINT_TYPES = [1 => 'parcelshop', 2 => 'locker'];

    public function __construct(private readonly GlsApi $api, private readonly GlsSettings $settings) {}

    public function key(): string
    {
        return 'gls';
    }

    public function label(): string
    {
        return 'GLS';
    }

    public function isConfigured(): bool
    {
        return filled($this->settings->username) && filled($this->settings->password) && filled($this->settings->client_number);
    }

    public function supports(ShippingType $type): bool
    {
        return in_array($type, [ShippingType::Delivery, ShippingType::PickupPoint], true);
    }

    public function pickupSelector(ShippingMethod $method): ?array
    {
        // Zoznam ParcelShopov a boxov je uložený v obchode (denná aktualizácia), zákazník ho prehľadáva.
        return ['list' => true];
    }

    public function methodFields(): array
    {
        $t = fn (string $key) => __('gls::admin.'.$key);

        return [
            Select::make('country')->label($t('country'))
                ->options(collect(GlsApi::COUNTRIES)->mapWithKeys(fn ($code) => [strtoupper($code) => strtoupper($code)])->all())
                ->default('SK')->selectablePlaceholder(false),
            CheckboxList::make('point_types')->label($t('point_types'))
                ->options(['parcelshop' => $t('types.parcelshop'), 'locker' => $t('types.locker')])
                ->default(['parcelshop', 'locker']),
        ];
    }

    public function createShipment(ShipmentRequest $request): ShipmentResult
    {
        $response = $this->api->call('ParcelService', 'PrintLabels', [
            'ParcelList' => [$this->parcel($request)],
            'TypeOfPrinter' => $this->settings->printer,
            'PrintPosition' => 1,
            'ShowPrintDialog' => false,
            'WebshopEngine' => 'Eshop',
        ]);

        GlsApi::throwErrors($response['PrintLabelsErrorList'] ?? null);
        $info = $response['PrintLabelsInfoList'][0] ?? null;

        if ($info === null) {
            throw new CarrierException(__('gls::admin.invalid_response'), CarrierException::UNAVAILABLE);
        }

        $label = GlsApi::bytes($response['Labels'] ?? null);

        return new ShipmentResult((string) $info['ParcelId'], (string) $info['ParcelNumber'], $label !== '' ? $label : null);
    }

    /**
     * Zásielka vo formáte MyGLS (trieda Parcel).
     *
     * @return array<string, mixed>
     */
    public function parcel(ShipmentRequest $request): array
    {
        [$street, $houseNumber] = $request->streetParts();
        $company = app(CompanySettings::class);
        $shop = app(GeneralSettings::class);
        $services = [];

        if ($request->pickupPoint !== null) {
            // Doručenie na ParcelShop alebo do boxu (PSD) vyžaduje kontakt na príjemcu.
            $code = (string) $request->pickupPoint['code'];
            $services[] = ['Code' => 'PSD', 'PSDParameter' => ctype_digit($code) ? ['IntegerValue' => (int) $code] : ['StringValue' => $code]];
        } elseif ($this->settings->notify_recipient) {
            $services[] = ['Code' => 'FDS', 'FDSParameter' => ['Value' => $request->email]];
        }

        $delivery = $request->pickupPoint !== null
            ? ['Street' => (string) ($request->pickupPoint['street'] ?? $street), 'City' => (string) ($request->pickupPoint['city'] ?? $request->city), 'ZipCode' => (string) ($request->pickupPoint['zip'] ?? $request->zip)]
            : ['Street' => $street, 'HouseNumber' => $houseNumber !== '' ? $houseNumber : null, 'City' => (string) $request->city, 'ZipCode' => str_replace(' ', '', (string) $request->zip)];

        return [
            'ClientNumber' => (int) $this->settings->client_number,
            'ClientReference' => $request->reference,
            'Count' => 1,
            'CODAmount' => $request->codAmount !== null ? round($request->codAmount / 100, 2) : 0,
            'CODReference' => $request->codAmount !== null ? $request->reference : null,
            'CODCurrency' => $request->codAmount !== null ? $request->currency : null,
            'Content' => $request->reference,
            'DeliveryAddress' => array_filter([
                'Name' => $request->company ?: $request->fullName(),
                ...$delivery,
                'CountryIsoCode' => strtoupper($request->pickupPoint['country'] ?? $request->country),
                'ContactName' => $request->fullName(),
                'ContactPhone' => $request->phone,
                'ContactEmail' => $request->email,
            ], fn ($value) => $value !== null && $value !== ''),
            'PickupAddress' => array_filter([
                'Name' => $company->company_name ?: $shop->shop_name,
                'Street' => (string) $company->street,
                'City' => (string) $company->city,
                'ZipCode' => str_replace(' ', '', (string) $company->zip),
                'CountryIsoCode' => strtoupper((string) ($company->country ?: $shop->country)),
                'ContactName' => $company->company_name ?: $shop->shop_name,
                'ContactPhone' => $shop->contact_phone,
                'ContactEmail' => $shop->contact_email,
            ], fn ($value) => $value !== null && $value !== ''),
            'ServiceList' => $services,
            'ParcelPropertyList' => [['Weight' => round(max(0.1, $request->weightGrams / 1000), 2)]],
        ];
    }

    public function labels(array $references): string
    {
        $response = $this->api->call('ParcelService', 'GetPrintedLabels', [
            'ParcelIdList' => array_map('intval', $references),
            'TypeOfPrinter' => $this->settings->printer,
            'PrintPosition' => 1,
            'ShowPrintDialog' => false,
        ]);

        GlsApi::throwErrors($response['GetPrintedLabelsErrorList'] ?? null);
        $pdf = GlsApi::bytes($response['Labels'] ?? null);

        if ($pdf === '') {
            throw new CarrierException(__('gls::admin.invalid_response'), CarrierException::UNAVAILABLE);
        }

        return $pdf;
    }

    public function track(string $reference, ?string $trackingNumber = null): ?TrackingResult
    {
        if (blank($trackingNumber)) {
            return null;
        }

        $response = $this->api->call('ParcelService', 'GetParcelStatuses', [
            'ParcelNumber' => (int) $trackingNumber,
            'ReturnPOD' => false,
            'LanguageIsoCode' => strtoupper(in_array(app()->getLocale(), ['sk', 'cs', 'hu'], true) ? app()->getLocale() : 'en'),
        ]);

        GlsApi::throwErrors($response['GetParcelStatusErrors'] ?? null);
        $statuses = $response['ParcelStatusList'] ?? [];

        if ($statuses === []) {
            return null;
        }

        // Najnovší stav podľa dátumu (dátumy WCF: /Date(1234567890000+0100)/).
        usort($statuses, fn ($a, $b) => self::date($a['StatusDate'] ?? null) <=> self::date($b['StatusDate'] ?? null));
        $last = end($statuses);

        return new TrackingResult(self::status((int) ($last['StatusCode'] ?? 0)), trim((string) ($last['StatusDescription'] ?? '')) ?: null);
    }

    /**
     * Stavové kódy GLS (MyGLS API, Appendix G).
     */
    public static function status(int $code): ShipmentStatus
    {
        return match (true) {
            $code === 51 => ShipmentStatus::Created,
            in_array($code, [5, 58, 59], true) => ShipmentStatus::Delivered,
            in_array($code, [54, 55, 56], true) => ShipmentStatus::ReadyForPickup,
            in_array($code, [23, 40], true) => ShipmentStatus::Returned,
            in_array($code, [28, 42], true) => ShipmentStatus::Cancelled,
            default => ShipmentStatus::InTransit,
        };
    }

    private static function date(mixed $value): int
    {
        if (is_string($value) && preg_match('/Date\((-?\d+)/', $value, $match)) {
            return (int) $match[1];
        }

        return is_string($value) ? (int) (Carbon::parse($value)->getTimestampMs()) : 0;
    }

    public function trackingUrl(string $trackingNumber, array $methodOptions = []): ?string
    {
        $paths = ['sk' => 'SK/sk/sledovanie-zasielok', 'cz' => 'CZ/cs/sledovani-zasilek', 'hu' => 'HU/hu/csomagkovetes'];
        $path = $paths[$this->settings->country] ?? 'SK/sk/sledovanie-zasielok';

        return 'https://gls-group.com/'.$path.'?match='.rawurlencode($trackingNumber);
    }

    public function testConnection(): void
    {
        // Pri nesprávnych údajoch vráti API chybu 401 (výnimka CONFIGURATION).
        $this->api->call('ParcelService', 'GetParcelStatuses', ['ParcelNumber' => 0, 'ReturnPOD' => false, 'LanguageIsoCode' => 'EN']);
    }

    public function syncPickupPoints(): int
    {
        $countries = ShippingMethod::query()->where('carrier', 'gls')->get()->toBase()
            ->map(fn (ShippingMethod $method) => strtoupper((string) ($method->carrier_options['country'] ?? $this->settings->country)))
            ->push(strtoupper($this->settings->country))->unique()->values();

        $points = [];

        foreach ($countries as $country) {
            $response = $this->api->call('MasterDataService', 'GetDeliveryPoints', ['CountryIsoCode' => $country]);

            if (($response['ErrorCode'] ?? 0) !== 0) {
                throw new CarrierException('GLS: '.($response['ErrorDescription'] ?? $response['ErrorCode']), CarrierException::INVALID_DATA);
            }

            $json = @gzdecode(GlsApi::bytes($response['Data'] ?? null));

            foreach ((array) json_decode((string) $json, true) as $point) {
                if (! is_array($point) || ! isset(self::POINT_TYPES[$point['DeliveryPointType'] ?? 0]) || ($point['IsActive'] ?? true) === false) {
                    continue;
                }

                $address = $point['Address'] ?? [];
                $points[] = [
                    'code' => (string) ($point['LegacyId'] ?? '') !== '' ? (string) $point['LegacyId'] : (string) $point['Id'],
                    'name' => (string) ($address['Name'] ?? $point['Matchcode'] ?? $point['Id']),
                    'street' => trim(($address['Street'] ?? '').' '.($address['HouseNumber'] ?? '')),
                    'city' => $address['City'] ?? null,
                    'zip' => $address['ZipCode'] ?? null,
                    'country' => (string) ($address['CountryIsoCode'] ?? $country),
                    'type' => self::POINT_TYPES[$point['DeliveryPointType']],
                    'latitude' => $point['Latitude'] ?? null,
                    'longitude' => $point['Longitude'] ?? null,
                    'opening_hours' => self::openingHours($point['DeliveryPointOpeningList'] ?? []),
                ];
            }
        }

        return PickupPoint::syncCarrier('gls', $points);
    }

    /**
     * Otváracie hodiny ako text: „Po 08:00-17:00, Ut …“.
     *
     * @param  list<array<string, mixed>>  $openings
     */
    private static function openingHours(array $openings): ?string
    {
        $days = [1 => 'Po', 2 => 'Ut', 3 => 'St', 4 => 'Št', 5 => 'Pi', 6 => 'So', 7 => 'Ne'];
        $parts = [];

        foreach ($openings as $opening) {
            if (filled($opening['Open'] ?? null) && isset($days[$opening['DayOfWeek'] ?? 0])) {
                $parts[] = $days[$opening['DayOfWeek']].' '.$opening['Open'];
            }
        }

        return $parts === [] ? null : implode(', ', $parts);
    }
}
