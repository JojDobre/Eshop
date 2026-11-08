<?php

namespace Modules\Balikobot;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
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
 * Kuriéri cez Balíkobot. Pri spôsobe dopravy sa vyberie kuriér a služba (service_type z Balíkobota).
 * Referencia zásielky má tvar „kuriér:package_id“, sledovacie číslo je číslo zásielky u kuriéra (carrier_id).
 * Výdajné miesta majú kód „kuriér:branch_id“ a typ „kuriér:služba“ (pre filtrovanie v pokladni).
 */
class BalikobotCarrier implements ShippingCarrier
{
    /** Najpoužívanejší kuriéri v CZ a SK (kód Balíkobota → názov). */
    public const SHIPPERS = [
        'dpd' => 'DPD', 'geis' => 'Geis', 'toptrans' => 'Toptrans', 'intime' => 'WE|DO', 'kurier' => '123kuriér',
        'gls' => 'GLS', 'ppl' => 'PPL', 'cp' => 'Česká pošta', 'sp' => 'Slovenská pošta', 'sps' => 'SPS',
        'zasilkovna' => 'Zásilkovna', 'dhl' => 'DHL Express', 'gw' => 'Gebrüder Weiss SK', 'gwcz' => 'Gebrüder Weiss CZ',
        'ups' => 'UPS', 'fofr' => 'FOFR', 'raben' => 'Raben', 'dbschenker' => 'DB Schenker',
    ];

    /** Verejné sledovanie zásielok (iba overené adresy). */
    private const TRACKING_URLS = [
        'dpd' => 'https://tracking.dpd.de/status/cs_CZ/parcel/{number}',
        'ppl' => 'https://www.ppl.cz/vyhledat-zasilku?shipmentId={number}',
        'cp' => 'https://www.postaonline.cz/trackandtrace/-/zasilka/cislo?parcelNumbers={number}',
        'sp' => 'https://tandt.posta.sk/zasielky/{number}',
        'sps' => 'https://www.sps-sro.sk/moja-zasielka/?shipment_id={number}',
    ];

    public function __construct(private readonly BalikobotApi $api, private readonly BalikobotSettings $settings) {}

    public function key(): string
    {
        return 'balikobot';
    }

    public function label(): string
    {
        return 'Balíkobot';
    }

    public function isConfigured(): bool
    {
        return filled($this->settings->api_user) && filled($this->settings->api_key);
    }

    public function supports(ShippingType $type): bool
    {
        return in_array($type, [ShippingType::Delivery, ShippingType::PickupPoint], true);
    }

    public function pickupSelector(ShippingMethod $method): ?array
    {
        return ['list' => true];
    }

    public function methodFields(): array
    {
        $t = fn (string $key) => __('balikobot::admin.'.$key);

        return [
            Select::make('shipper')->label($t('shipper'))->required()->options(self::SHIPPERS)->searchable()->live(),
            TextInput::make('service_type')->label($t('service_type'))->helperText($t('service_type_help'))->maxLength(20),
            // Filter výdajných miest v pokladni: iba miesta zvoleného kuriéra a služby.
            Hidden::make('point_types')->dehydrateStateUsing(fn ($state, $get) => filled($get('shipper')) ? [self::pointType((string) $get('shipper'), (string) $get('service_type'))] : []),
        ];
    }

    public static function pointType(string $shipper, ?string $service): string
    {
        return mb_substr($shipper.':'.($service ?? ''), 0, 32);
    }

    /**
     * Zásielka pre metódu add.
     *
     * @return array<string, mixed>
     */
    public function package(ShipmentRequest $request): array
    {
        $package = [
            'eid' => mb_substr($request->reference, 0, 40),
            'service_type' => filled($request->methodOptions['service_type'] ?? null) ? (string) $request->methodOptions['service_type'] : null,
            'rec_name' => $request->fullName(),
            'rec_firm' => $request->company,
            'rec_street' => $request->street,
            'rec_city' => $request->city,
            'rec_zip' => $request->zip !== null ? str_replace(' ', '', $request->zip) : null,
            'rec_country' => strtoupper($request->country),
            'rec_email' => $request->email,
            'rec_phone' => $request->phone,
            'price' => round($request->value / 100, 2),
            'ins_currency' => $request->currency,
            'weight' => round(max(0.1, $request->weightGrams / 1000), 2),
            'real_order_id' => mb_substr($request->reference, 0, 20),
            'note' => $request->note ? mb_substr($request->note, 0, 100) : null,
        ];

        if ($request->pickupPoint !== null) {
            // Kód miesta v obchode „kuriér:branch_id“ → branch_id.
            $code = (string) $request->pickupPoint['code'];
            $package['branch_id'] = str_contains($code, ':') ? substr($code, strpos($code, ':') + 1) : $code;
        }

        if ($request->codAmount !== null) {
            $package['cod_price'] = round($request->codAmount / 100, 2);
            $package['cod_currency'] = $request->currency;
            $package['vs'] = substr(preg_replace('/\D/', '', $request->reference) ?: '0', -10);
        }

        return array_filter($package, fn ($value) => $value !== null && $value !== '');
    }

    public function createShipment(ShipmentRequest $request): ShipmentResult
    {
        $shipper = self::shipper($request->methodOptions);
        $response = $this->api->call($shipper.'/add', ['packages' => [$this->package($request)]]);
        $package = $response['packages'][0] ?? null;

        if (! is_array($package) || blank($package['package_id'] ?? null)) {
            throw new CarrierException('Balíkobot: '.BalikobotApi::errors($response), CarrierException::INVALID_DATA);
        }

        if ($this->settings->auto_order) {
            // Odovzdanie dát kuriérovi (bez neho kuriér o zásielke nevie).
            $this->api->call($shipper.'/order', ['package_ids' => [$package['package_id']]]);
        }

        $label = filled($package['label_url'] ?? null) ? $this->download((string) $package['label_url']) : null;

        return new ShipmentResult($shipper.':'.$package['package_id'], filled($package['carrier_id'] ?? null) ? (string) $package['carrier_id'] : null, $label);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private static function shipper(array $options): string
    {
        $shipper = (string) ($options['shipper'] ?? '');

        if (! preg_match('/^[a-z0-9]+$/', $shipper)) {
            throw new CarrierException(__('balikobot::admin.missing_shipper'), CarrierException::CONFIGURATION);
        }

        return $shipper;
    }

    private function download(string $url): ?string
    {
        try {
            $response = Http::timeout(60)->get($url);
        } catch (ConnectionException) {
            return null;
        }

        return $response->successful() && str_starts_with($response->body(), '%PDF') ? $response->body() : null;
    }

    public function labels(array $references): string
    {
        $groups = collect($references)->map(fn ($reference) => explode(':', (string) $reference, 2))
            ->filter(fn ($parts) => count($parts) === 2)
            ->groupBy(0);

        if ($groups->count() !== 1) {
            throw new CarrierException(__('balikobot::admin.labels_one_shipper'), CarrierException::INVALID_DATA);
        }

        $shipper = (string) $groups->keys()->first();
        $response = $this->api->call($shipper.'/labels', ['package_ids' => $groups->first()->pluck(1)->values()->all()]);
        $pdf = filled($response['labels_url'] ?? null) ? $this->download((string) $response['labels_url']) : null;

        if ($pdf === null) {
            throw new CarrierException(__('balikobot::admin.invalid_response'), CarrierException::UNAVAILABLE);
        }

        return $pdf;
    }

    public function track(string $reference, ?string $trackingNumber = null): ?TrackingResult
    {
        [$shipper] = explode(':', $reference, 2);

        if (blank($trackingNumber) || ! preg_match('/^[a-z0-9]+$/', $shipper)) {
            return null;
        }

        $response = $this->api->call($shipper.'/trackstatus', ['carrier_ids' => [$trackingNumber]]);
        $package = $response['packages'][0] ?? null;

        if (! is_array($package) || ! isset($package['status_id'])) {
            return null;
        }

        return new TrackingResult(self::status((float) $package['status_id']), trim((string) ($package['status_text'] ?? '')) ?: null);
    }

    /**
     * Jednotné stavy Balíkobota (status_id) → stav zásielky v obchode.
     */
    public static function status(float $id): ShipmentStatus
    {
        return match (true) {
            $id === -1.0 => ShipmentStatus::Created,
            $id === 1.1 || $id === 1.2 || $id === 5.0 => ShipmentStatus::Delivered,
            $id === 2.3 => ShipmentStatus::ReadyForPickup,
            $id === 4.0 => ShipmentStatus::Returned,
            $id >= 3.0 && $id < 4.0 => ShipmentStatus::Cancelled,
            default => ShipmentStatus::InTransit,
        };
    }

    public function trackingUrl(string $trackingNumber, array $methodOptions = []): ?string
    {
        $template = self::TRACKING_URLS[$methodOptions['shipper'] ?? ''] ?? null;

        return $template !== null ? str_replace('{number}', rawurlencode($trackingNumber), $template) : null;
    }

    public function testConnection(): void
    {
        $this->api->call('info/whoami');
    }

    public function syncPickupPoints(): int
    {
        // Kuriéri a služby zo spôsobov dopravy s výdajnými miestami.
        $sources = ShippingMethod::query()->where('carrier', 'balikobot')->get()->toBase()
            ->filter(fn (ShippingMethod $method) => $method->type->requiresPickupPoint() && filled($method->carrier_options['shipper'] ?? null))
            ->map(fn (ShippingMethod $method) => [(string) $method->carrier_options['shipper'], (string) ($method->carrier_options['service_type'] ?? '')])
            ->unique(fn ($source) => implode(':', $source))->values();

        return PickupPoint::syncCarrier('balikobot', $this->branches($sources->all()));
    }

    /**
     * @param  list<array{0: string, 1: string}>  $sources
     * @return \Generator<array<string, mixed>>
     */
    private function branches(array $sources): \Generator
    {
        foreach ($sources as [$shipper, $service]) {
            $response = $this->api->call(self::shipper(['shipper' => $shipper]).'/branches'.($service !== '' ? '/service/'.rawurlencode($service) : ''));

            foreach ((array) ($response['branches'] ?? []) as $branch) {
                $id = $branch['branch_id'] ?? $branch['id'] ?? null;

                if (! is_array($branch) || blank($id)) {
                    continue;
                }

                yield [
                    'code' => mb_substr($shipper.':'.$id, 0, 64),
                    'name' => (string) ($branch['name'] ?? $id),
                    'street' => $branch['street'] ?? null,
                    'city' => $branch['city'] ?? null,
                    'zip' => $branch['zip'] ?? null,
                    'country' => (string) ($branch['country'] ?? 'CZ'),
                    'type' => self::pointType($shipper, $service),
                    'latitude' => is_numeric($branch['latitude'] ?? null) ? (float) $branch['latitude'] : null,
                    'longitude' => is_numeric($branch['longitude'] ?? null) ? (float) $branch['longitude'] : null,
                ];
            }
        }
    }
}
