<?php

namespace Modules\CeskaPosta;

use App\Settings\CompanySettings;
use App\Settings\GeneralSettings;
use Filament\Forms\Components\CheckboxList;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\File;
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
use XMLReader;

/**
 * Česká pošta: Balík Do ruky (DR) a Balíkovna (NB) cez B2B API, štítky, sledovanie, dobierka.
 */
class CeskaPostaCarrier implements ShippingCarrier
{
    /** Verejný zoznam pobočiek Balíkovny (pošty, partneri, boxy), aktualizovaný denne. */
    public const POINTS_URL = 'https://napostu.ceskaposta.cz/vystupy/balikovny.xml';

    /** Typy pobočiek zo zoznamu → typ výdajného miesta v obchode (depá sa neponúkajú). */
    private const POINT_TYPES = ['pošta' => 'post_office', 'balíkovna partner' => 'partner', 'balíkovna-BOX' => 'box'];

    /** Služba 41 = dobierka. */
    private const SERVICE_COD = '41';

    public function __construct(private readonly CeskaPostaApi $api, private readonly CeskaPostaSettings $settings) {}

    public function key(): string
    {
        return 'ceskaposta';
    }

    public function label(): string
    {
        return __('ceskaposta::admin.carrier');
    }

    public function isConfigured(): bool
    {
        return filled($this->settings->api_token) && filled($this->settings->secret_key)
            && filled($this->settings->customer_id) && filled($this->settings->post_code);
    }

    public function supports(ShippingType $type): bool
    {
        return in_array($type, [ShippingType::Delivery, ShippingType::PickupPoint], true);
    }

    public function pickupSelector(ShippingMethod $method): ?array
    {
        // Pobočky Balíkovny sú uložené v obchode (denná aktualizácia), zákazník ich prehľadáva.
        return ['list' => true];
    }

    public function methodFields(): array
    {
        $t = fn (string $key) => __('ceskaposta::admin.'.$key);

        return [
            CheckboxList::make('point_types')->label($t('point_types'))
                ->options(collect(self::POINT_TYPES)->mapWithKeys(fn ($type) => [$type => $t('types.'.$type)])->all())
                ->default(array_values(self::POINT_TYPES)),
        ];
    }

    public function createShipment(ShipmentRequest $request): ShipmentResult
    {
        $response = $this->api->request('POST', '/parcelService', $this->payload($request));
        $header = $response['responseHeader'] ?? [];
        $parcel = $header['resultParcelData'][0] ?? [];

        CeskaPostaApi::throwErrors($parcel['parcelStateResponse'] ?? null);

        if (blank($parcel['parcelCode'] ?? null)) {
            CeskaPostaApi::throwErrors([$header['resultHeader'] ?? []]);

            throw new CarrierException(__('ceskaposta::admin.invalid_response'), CarrierException::UNAVAILABLE);
        }

        $label = base64_decode((string) ($header['responsePrintParams']['file'] ?? ''), true);
        $code = (string) $parcel['parcelCode'];

        return new ShipmentResult($code, $code, $label !== false && $label !== '' ? $label : null);
    }

    /**
     * Požiadavka parcelService: hlavička podania (odosielateľ, štítok) a jedna zásielka.
     *
     * @return array<string, mixed>
     */
    public function payload(ShipmentRequest $request): array
    {
        $company = app(CompanySettings::class);
        $shop = app(GeneralSettings::class);
        [$senderStreet, $senderNumber] = self::splitStreet((string) $company->street);
        $services = array_values(array_filter([$this->settings->parcel_size ?: null]));

        $params = [
            'recordID' => $request->reference,
            'prefixParcelCode' => $request->pickupPoint !== null ? 'NB' : 'DR',
            'weight' => number_format(max(0.1, $request->weightGrams / 1000), 3, '.', ''),
            'insuredValue' => round($request->value / 100, 2),
            'notePrint' => mb_substr($request->reference, 0, 50),
        ];

        if ($request->codAmount !== null) {
            $params['amount'] = round($request->codAmount / 100, 2);
            $params['currency'] = $request->currency;
            // Variabilný symbol smie obsahovať iba číslice.
            $params['vsVoucher'] = substr(preg_replace('/\D/', '', $request->reference) ?: '0', -10);
            $services[] = self::SERVICE_COD;
        }

        return [
            'parcelServiceHeader' => [
                'parcelServiceHeaderCom' => array_filter([
                    'transmissionDate' => now()->toDateString(),
                    'customerID' => (string) $this->settings->customer_id,
                    'postCode' => str_replace(' ', '', (string) $this->settings->post_code),
                    'locationNumber' => $this->settings->location_number,
                    'contractNumber' => $this->settings->contract_number,
                ], fn ($value) => $value !== null && $value !== ''),
                'printParams' => ['idForm' => $this->settings->label_form, 'shiftHorizontal' => 0, 'shiftVertical' => 0],
                'position' => 1,
                'senderAddress' => array_filter([
                    'companyName' => mb_substr((string) ($company->company_name ?: $shop->shop_name), 0, 50),
                    'street' => $senderStreet,
                    'houseNumber' => $senderNumber,
                    'city' => (string) $company->city,
                    'zipCode' => str_replace(' ', '', (string) $company->zip),
                    'isoCountry' => strtoupper((string) ($company->country ?: 'CZ')),
                ], fn ($value) => $value !== ''),
                'senderContacts' => array_filter(['mobilNumber' => self::phone((string) $shop->contact_phone), 'emailAddress' => (string) $shop->contact_email]),
            ],
            'parcelServiceData' => [
                'parcelParams' => $params,
                'parcelServices' => $services,
                'parcelAddress' => $this->address($request),
            ],
        ];
    }

    /**
     * Adresa príjemcu; pri Balíkovne je to pobočka (ulica „Balíkovna“, názov a PSČ pobočky).
     *
     * @return array<string, mixed>
     */
    private function address(ShipmentRequest $request): array
    {
        if ($request->pickupPoint !== null) {
            $address = ['street' => 'Balíkovna', 'city' => (string) ($request->pickupPoint['name'] ?? ''), 'zipCode' => (string) $request->pickupPoint['code'], 'isoCountry' => 'CZ'];
        } else {
            [$street, $houseNumber] = $request->streetParts();
            $address = array_filter([
                'street' => $street,
                'houseNumber' => $houseNumber,
                'city' => (string) $request->city,
                'zipCode' => str_replace(' ', '', (string) $request->zip),
                'isoCountry' => strtoupper($request->country),
            ], fn ($value) => $value !== '');
        }

        return array_filter([
            'firstName' => $request->firstName,
            'surname' => $request->lastName,
            'company' => $request->company,
            'address' => $address,
            'mobilNumber' => self::phone($request->phone),
            'emailAddress' => $request->email,
        ], fn ($value) => $value !== null && $value !== '');
    }

    /**
     * Ulica a číslo domu zvlášť („Hlavná 12/A“ → [„Hlavná“, „12/A“]).
     *
     * @return array{0: string, 1: string}
     */
    private static function splitStreet(string $street): array
    {
        $street = trim($street);

        return preg_match('/^(.*?)[\s,]+(\d[\w\/\-]*)$/u', $street, $match) ? [trim($match[1]), $match[2]] : [$street, ''];
    }

    /** Telefón bez medzier a bez predvoľby +420 (pošta ho pre ČR vyžaduje v tvare 9 číslic). */
    public static function phone(string $phone): string
    {
        $phone = preg_replace('/[^\d+]/', '', $phone) ?? '';

        return preg_replace('/^(\+|00)420(?=\d{9}$)/', '', $phone) ?? $phone;
    }

    public function labels(array $references): string
    {
        $response = $this->api->request('POST', '/parcelPrinting', [
            'printingHeader' => array_filter([
                'customerID' => (string) $this->settings->customer_id,
                'contractNumber' => $this->settings->contract_number,
                'idForm' => $this->settings->label_form,
                'shiftHorizontal' => 0,
                'shiftVertical' => 0,
                'position' => 1,
            ], fn ($value) => $value !== null),
            'printingData' => array_values($references),
        ]);

        CeskaPostaApi::throwErrors([$response['printingHeaderResult']['printingStatusResponse'] ?? []]);
        $pdf = base64_decode((string) ($response['printingDataResult'] ?? ''), true);

        if ($pdf === false || $pdf === '') {
            throw new CarrierException(__('ceskaposta::admin.invalid_response'), CarrierException::UNAVAILABLE);
        }

        return $pdf;
    }

    public function track(string $reference, ?string $trackingNumber = null): ?TrackingResult
    {
        $response = $this->api->request('GET', '/parcelStatuses/current/idParcel/'.rawurlencode($trackingNumber ?: $reference));
        $status = $response['parcelStatus'] ?? null;

        if (! is_array($status) || blank($status['statusID'] ?? null)) {
            return null;
        }

        $text = trim((string) ($status['statusDescription'] ?? '')) ?: null;

        return new TrackingResult(self::status((string) $status['statusID'], (string) $text), $text);
    }

    /**
     * Stav zásielky podľa ID stavu a jeho popisu (číselník stavov pošta nezverejňuje, popisy sú stabilné).
     */
    public static function status(string $id, string $description = ''): ShipmentStatus
    {
        $text = mb_strtolower($description);

        return match (true) {
            str_starts_with($id, '-') || str_contains($text, 'údaje') || str_contains($text, 'data') => ShipmentStatus::Created,
            str_contains($text, 'vrác') || str_contains($text, 'vrace') || str_contains($text, 'zpět') => ShipmentStatus::Returned,
            str_contains($text, 'připravena') || str_contains($text, 'uložen') || str_contains($text, 'k vyzvednutí') => ShipmentStatus::ReadyForPickup,
            str_contains($text, 'dodán') || str_contains($text, 'doručen') || str_contains($text, 'vyzvednuta') => ShipmentStatus::Delivered,
            str_contains($text, 'storn') || str_contains($text, 'zrušen') => ShipmentStatus::Cancelled,
            default => ShipmentStatus::InTransit,
        };
    }

    public function trackingUrl(string $trackingNumber, array $methodOptions = []): ?string
    {
        return 'https://www.postaonline.cz/trackandtrace/-/zasilka/cislo?parcelNumbers='.rawurlencode($trackingNumber);
    }

    public function testConnection(): void
    {
        // Overenie podpisu: neexistujúca zásielka vráti 404/400, nesprávne kľúče 401/403 (výnimka CONFIGURATION).
        try {
            $this->api->request('GET', '/parcelStatuses/current/idParcel/DR0000000000U');
        } catch (CarrierException $e) {
            if ($e->type !== CarrierException::INVALID_DATA) {
                throw $e;
            }
        }
    }

    public function syncPickupPoints(): int
    {
        // Zoznam má desiatky MB: stiahne sa do súboru a číta sa po riadkoch (nízka spotreba pamäte).
        $path = storage_path('app/eshop/tmp/ceskaposta-points.xml');
        File::ensureDirectoryExists(dirname($path));

        try {
            $response = Http::timeout(300)->sink($path)->get(self::POINTS_URL);
        } catch (ConnectionException $e) {
            throw new CarrierException(__('ceskaposta::admin.unavailable'), CarrierException::UNAVAILABLE, $e);
        }

        try {
            if (! $response->successful()) {
                throw new CarrierException(__('ceskaposta::admin.unavailable'), CarrierException::UNAVAILABLE);
            }

            return PickupPoint::syncCarrier('ceskaposta', $this->readPoints($path));
        } finally {
            File::delete($path);
        }
    }

    /**
     * Pobočky zo súboru balikovny.xml.
     *
     * @return \Generator<array<string, mixed>>
     */
    public function readPoints(string $path): \Generator
    {
        $reader = new XMLReader;

        if (! $reader->open($path, 'UTF-8', LIBXML_NONET)) {
            throw new CarrierException(__('ceskaposta::admin.invalid_response'), CarrierException::UNAVAILABLE);
        }

        try {
            while ($reader->read()) {
                if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'row') {
                    continue;
                }

                $row = @simplexml_load_string($reader->readOuterXml());
                $type = self::POINT_TYPES[trim((string) $row?->TYP)] ?? null;

                if ($row === false || $type === null || trim((string) $row->PSC) === '') {
                    continue;
                }

                // ADRESA: „Ulica číslo, časť obce, PSČ, obec“.
                $parts = array_map('trim', explode(',', (string) $row->ADRESA));

                yield [
                    'code' => trim((string) $row->PSC),
                    'name' => trim((string) $row->NAZEV),
                    'street' => count($parts) > 2 && $parts[0] !== '' ? $parts[0] : null,
                    'city' => trim((string) $row->OBEC) ?: (end($parts) ?: null),
                    'zip' => count($parts) > 1 ? $parts[count($parts) - 2] : trim((string) $row->PSC),
                    'country' => 'CZ',
                    'type' => $type,
                    'latitude' => is_numeric((string) $row->SOUR_Y_WGS84) ? (float) $row->SOUR_Y_WGS84 : null,
                    'longitude' => is_numeric((string) $row->SOUR_X_WGS84) ? (float) $row->SOUR_X_WGS84 : null,
                    'opening_hours' => self::openingHours($row->OTEV_DOBY),
                ];
            }
        } finally {
            $reader->close();
        }
    }

    /**
     * Otváracie hodiny ako text: „Po 08:00-19:30, Ut …“.
     */
    private static function openingHours(?\SimpleXMLElement $hours): ?string
    {
        $days = ['Pondělí' => 'Po', 'Úterý' => 'Ut', 'Středa' => 'St', 'Čtvrtek' => 'Št', 'Pátek' => 'Pi', 'Sobota' => 'So', 'Neděle' => 'Ne'];
        $parts = [];

        foreach ($hours?->den ?? [] as $day) {
            $ranges = [];

            foreach ($day->od_do as $range) {
                $ranges[] = trim((string) $range->od).'-'.trim((string) $range->do);
            }

            if ($ranges !== [] && isset($days[(string) $day['name']])) {
                $parts[] = $days[(string) $day['name']].' '.implode(', ', $ranges);
            }
        }

        return $parts === [] ? null : implode(', ', $parts);
    }
}
