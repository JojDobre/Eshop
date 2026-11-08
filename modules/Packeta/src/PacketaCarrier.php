<?php

namespace Modules\Packeta;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Modules\Shipping\Carriers\CarrierException;
use Modules\Shipping\Carriers\ShipmentRequest;
use Modules\Shipping\Carriers\ShipmentResult;
use Modules\Shipping\Carriers\ShipmentStatus;
use Modules\Shipping\Carriers\ShippingCarrier;
use Modules\Shipping\Carriers\TrackingResult;
use Modules\Shipping\Models\ShippingMethod;
use Modules\Shipping\ShippingType;

/**
 * Packeta: výdajné miesta a Z-BOXy (mapa Packeta), doručenie domov cez dopravcov Packeta,
 * štítky v PDF, sledovanie zásielky a dobierka.
 */
class PacketaCarrier implements ShippingCarrier
{
    public const WIDGET_SCRIPT = 'https://widget.packeta.com/v6/www/js/library.js';

    public const COUNTRIES = ['sk', 'cz', 'hu', 'pl', 'ro', 'at', 'de'];

    public function __construct(private readonly PacketaApi $api, private readonly PacketaSettings $settings) {}

    public function key(): string
    {
        return 'packeta';
    }

    public function label(): string
    {
        return 'Packeta';
    }

    public function isConfigured(): bool
    {
        return filled($this->settings->api_password) && filled($this->settings->api_key);
    }

    public function supports(ShippingType $type): bool
    {
        return in_array($type, [ShippingType::Delivery, ShippingType::PickupPoint], true);
    }

    public function pickupSelector(ShippingMethod $method): ?array
    {
        $options = $method->carrier_options ?? [];
        $country = (string) ($options['country'] ?? 'sk');
        $groups = (array) ($options['point_groups'] ?? ['points', 'zbox']);
        $vendors = [];

        // Výdajné miesta Packeta (bez skupiny) a Z-BOXy (skupina zbox) v krajine spôsobu dopravy.
        if (in_array('points', $groups, true)) {
            $vendors[] = ['country' => $country];
        }

        if (in_array('zbox', $groups, true)) {
            $vendors[] = ['country' => $country, 'group' => 'zbox'];
        }

        return [
            'widget' => 'packeta',
            'scripts' => [self::WIDGET_SCRIPT],
            'config' => [
                'apiKey' => (string) $this->settings->api_key,
                'options' => ['language' => app()->getLocale() === 'cs' ? 'cs' : app()->getLocale(), 'vendors' => $vendors],
            ],
            // Výsledok okna Packeta sa prevedie na údaje výdajného miesta obchodu.
            'adapter' => <<<'JS'
window.eshopPickupWidgets.packeta = (config, done) => {
    if (! window.Packeta) { done(null); return; }
    window.Packeta.Widget.pick(config.apiKey, (point) => {
        if (! point) { done(null); return; }
        done({
            code: String(point.id || point.carrierId),
            name: point.place ? point.place + ', ' + point.name : point.name,
            street: point.street || '',
            city: point.city || '',
            zip: point.zip || '',
            country: (point.country || '').toUpperCase(),
            carrier_code: point.carrierPickupPointId || '',
        });
    }, config.options);
};
JS,
        ];
    }

    public function methodFields(): array
    {
        $t = fn (string $key) => __('packeta::admin.'.$key);

        return [
            Select::make('country')->label($t('country'))
                ->options(collect(self::COUNTRIES)->mapWithKeys(fn ($code) => [$code => strtoupper($code)])->all())
                ->default('sk')->selectablePlaceholder(false),
            CheckboxList::make('point_groups')->label($t('point_groups'))
                ->options(['points' => $t('groups.points'), 'zbox' => $t('groups.zbox')])
                ->default(['points', 'zbox']),
            TextInput::make('carrier_id')->label($t('carrier_id'))->helperText($t('carrier_id_help'))->integer(),
        ];
    }

    public function createShipment(ShipmentRequest $request): ShipmentResult
    {
        $point = $request->pickupPoint;
        $addressId = $point !== null ? (string) $point['code'] : (string) ($request->methodOptions['carrier_id'] ?? '');

        if ($addressId === '') {
            throw new CarrierException(__('packeta::admin.missing_carrier_id'), CarrierException::CONFIGURATION);
        }

        [$street, $houseNumber] = $request->streetParts();
        $isHomeDelivery = $point === null;

        $attributes = [
            'number' => $request->reference,
            'name' => mb_substr($request->firstName, 0, 32),
            'surname' => mb_substr($request->lastName, 0, 32),
            'company' => $request->company ? mb_substr($request->company, 0, 32) : null,
            'email' => $request->email,
            'phone' => $request->phone,
            'addressId' => $addressId,
            'carrierPickupPoint' => filled($point['carrier_code'] ?? null) ? $point['carrier_code'] : null,
            'currency' => $request->currency,
            'cod' => $request->codAmount !== null ? self::amount($request->codAmount, $request->currency) : null,
            'value' => self::amount($request->value, $request->currency),
            'weight' => number_format(max(0.01, $request->weightGrams / 1000), 3, '.', ''),
            'eshop' => filled($this->settings->sender) ? $this->settings->sender : null,
            'street' => $isHomeDelivery ? mb_substr($street, 0, 32) : null,
            'houseNumber' => $isHomeDelivery && $houseNumber !== '' ? mb_substr($houseNumber, 0, 16) : null,
            'city' => $isHomeDelivery ? mb_substr((string) $request->city, 0, 32) : null,
            'zip' => $isHomeDelivery ? str_replace(' ', '', (string) $request->zip) : null,
        ];

        $result = $this->api->call('createPacket', ['packetAttributes' => $attributes]);
        $id = (string) $result->id;

        return new ShipmentResult($id, (string) $result->barcode ?: 'Z'.$id, $this->singleLabel($id));
    }

    /** Štítok jednej zásielky (null, ak ho Packeta ešte nevie vytvoriť). */
    private function singleLabel(string $id): ?string
    {
        try {
            return base64_decode((string) $this->api->call('packetLabelPdf', [
                'packetId' => $id, 'format' => $this->settings->label_format, 'offset' => 0,
            ]), true) ?: null;
        } catch (CarrierException) {
            return null;
        }
    }

    public function labels(array $references): string
    {
        $result = $this->api->call('packetsLabelsPdf', [
            'packetIds' => ['_item' => 'id', ...array_values($references)],
            'format' => $this->settings->label_format,
            'offset' => 0,
        ]);

        $pdf = base64_decode((string) $result, true);

        if ($pdf === false || $pdf === '') {
            throw new CarrierException(__('packeta::admin.invalid_response'), CarrierException::UNAVAILABLE);
        }

        return $pdf;
    }

    public function track(string $reference, ?string $trackingNumber = null): ?TrackingResult
    {
        $result = $this->api->call('packetStatus', ['packetId' => $reference]);

        return new TrackingResult(self::status((int) $result->statusCode), trim((string) $result->statusText) ?: null);
    }

    /**
     * Stavové kódy Packeta (docs.packeta.com → Packet tracking → Status codes).
     */
    public static function status(int $code): ShipmentStatus
    {
        return match (true) {
            $code === 1 => ShipmentStatus::Created,
            $code === 5 => ShipmentStatus::ReadyForPickup,
            $code === 7 => ShipmentStatus::Delivered,
            $code === 11 => ShipmentStatus::Cancelled,
            in_array($code, [9, 10, 17, 18, 19, 20, 21, 22], true) => ShipmentStatus::Returned,
            default => ShipmentStatus::InTransit,
        };
    }

    public function trackingUrl(string $trackingNumber, array $methodOptions = []): ?string
    {
        $locale = in_array(app()->getLocale(), ['sk', 'cs', 'en'], true) ? app()->getLocale() : 'en';

        return 'https://tracking.packeta.com/'.$locale.'/?id='.rawurlencode($trackingNumber);
    }

    public function testConnection(): void
    {
        try {
            // Neexistujúca zásielka: pri správnom hesle príde chyba zásielky, nie hesla.
            $this->api->call('packetStatus', ['packetId' => '1']);
        } catch (CarrierException $e) {
            if ($e->type !== CarrierException::INVALID_DATA) {
                throw $e;
            }
        }
    }

    public function syncPickupPoints(): int
    {
        // Výdajné miesta vyberá zákazník priamo v mape Packeta.
        return 0;
    }

    /**
     * Suma z najmenších jednotiek (CZK celé koruny, HUF násobok 5).
     */
    public static function amount(int $minor, string $currency): string
    {
        $major = $minor / 100;

        return match (strtoupper($currency)) {
            'CZK' => (string) (int) round($major),
            'HUF' => (string) ((int) round($major / 5) * 5),
            default => number_format($major, 2, '.', ''),
        };
    }
}
