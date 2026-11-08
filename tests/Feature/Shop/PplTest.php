<?php

namespace Tests\Feature\Shop;

use App\Settings\CompanySettings;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Livewire\Livewire;
use Modules\Ppl\Filament\Pages\ManagePplSettings;
use Modules\Ppl\PplCarrier;
use Modules\Ppl\PplSettings;
use Modules\Shipping\Carriers\CarrierException;
use Modules\Shipping\Carriers\ShipmentRequest;
use Modules\Shipping\Carriers\ShipmentStatus;
use Modules\Shipping\Models\PickupPoint;

class PplTest extends ShopTestCase
{
    private const API = 'api.dhl.com/ecs/ppl/myapi2';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Sleep::fake();
    }

    private function carrier(): PplCarrier
    {
        $settings = app(PplSettings::class);
        $settings->client_id = 'obchod-client';
        $settings->client_secret = 'tajne-heslo-123';
        $settings->save();

        $company = app(CompanySettings::class);
        $company->fill(['company_name' => 'Obchod s.r.o.', 'street' => 'Dlouhá 15', 'city' => 'Praha', 'zip' => '110 00', 'country' => 'CZ', 'iban' => 'CZ65 0800 0000 1920 0014 5399'])->save();

        return app(PplCarrier::class);
    }

    private function request(array $overrides = []): ShipmentRequest
    {
        return new ShipmentRequest(...[
            'reference' => '2026000012', 'firstName' => 'Jana', 'lastName' => 'Nováková', 'company' => null,
            'email' => 'jana@example.com', 'phone' => '+420777123456', 'street' => 'Masarykova 12', 'city' => 'Brno',
            'zip' => '602 00', 'country' => 'CZ', 'pickupPoint' => null, 'codAmount' => 59000, 'currency' => 'CZK',
            'value' => 59000, 'weightGrams' => 1250, 'methodOptions' => [], ...$overrides,
        ]);
    }

    /**
     * @param  list<string>  $states  stavy dávky pri postupných dotazoch
     */
    private function fakeApi(array $states = ['InProcess', 'Complete']): void
    {
        $poll = 0;
        Http::fake(function (Request $request) use (&$poll, $states) {
            $url = $request->url();

            return match (true) {
                str_contains($url, '/login/getAccessToken') => Http::response(['access_token' => 'token-1', 'expires_in' => 1800]),
                $request->method() === 'POST' && str_ends_with($url, '/shipment/batch') => Http::response('', 201, ['Location' => 'https://'.self::API.'/shipment/batch/b-77']),
                str_ends_with($url, '/shipment/batch/b-77') => Http::response([
                    'items' => [array_filter([
                        'referenceId' => '2026000012',
                        'importState' => $state = $states[min($poll++, count($states) - 1)],
                        'shipmentNumber' => $state === 'Complete' ? '40990012345' : null,
                        'labelUrl' => $state === 'Complete' ? 'https://'.self::API.'/data/label-1' : null,
                        'errorMessage' => $state === 'Error' ? 'Neplatné PSČ' : null,
                    ])],
                    'completeLabel' => ['labelUrls' => ['https://'.self::API.'/data/label-all']],
                ]),
                str_ends_with($url, '/data/label-1') => Http::response('%PDF-label-1'),
                str_ends_with($url, '/data/label-all') => Http::response('%PDF-all'),
                str_contains($url, '/shipment?') => Http::response([[
                    'shipmentNumber' => '40990012345',
                    'trackAndTrace' => ['events' => [
                        ['phase' => 'Order', 'name' => 'Data přijata', 'eventDate' => '2026-11-08T08:00:00'],
                        ['phase' => 'PickupPoint', 'name' => 'Zásilka je připravena k vyzvednutí', 'eventDate' => '2026-11-09T10:00:00'],
                    ]],
                ]]),
                default => Http::response(['title' => 'Not found'], 404),
            };
        });
    }

    public function test_home_delivery_with_cod_waits_for_batch_and_downloads_label(): void
    {
        $carrier = $this->carrier();
        $this->fakeApi();

        $result = $carrier->createShipment($this->request());

        $this->assertSame('b-77', $result->reference);
        $this->assertSame('40990012345', $result->trackingNumber);
        $this->assertSame('%PDF-label-1', $result->label);
        Sleep::assertSleptTimes(1);

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'getAccessToken')
            && $request['grant_type'] === 'client_credentials' && $request['client_id'] === 'obchod-client');

        Http::assertSent(function (Request $request) {
            if ($request->method() !== 'POST' || ! str_ends_with($request->url(), '/shipment/batch')) {
                return false;
            }

            $shipment = $request['shipments'][0];

            return $request->header('Authorization')[0] === 'Bearer token-1'
                && $shipment['productType'] === 'PRID'
                && $shipment['recipient']['zipCode'] === '60200'
                && $shipment['cashOnDelivery'] == ['codPrice' => 590, 'codCurrency' => 'CZK', 'codVarSym' => '2026000012', 'iban' => 'CZ6508000000192000145399']
                && $shipment['sender']['name'] === 'Obchod s.r.o.'
                && $request['labelSettings']['completeLabelSettings']['pageSize'] === 'A4';
        });

        // Druhé volanie použije token z cache.
        $this->assertSame('%PDF-all', $carrier->labels(['b-77']));
        $this->assertCount(1, Http::recorded(fn (Request $request) => str_contains($request->url(), 'getAccessToken')));
    }

    public function test_product_types_pickup_point_tracking_and_errors(): void
    {
        $carrier = $this->carrier();

        $this->assertSame('SMAR', $carrier->productType($this->request(['codAmount' => null, 'pickupPoint' => ['code' => 'KM12345']])));
        $this->assertSame('SMAD', $carrier->productType($this->request(['pickupPoint' => ['code' => 'KM12345']])));
        $this->assertSame('CONN', $carrier->productType($this->request(['codAmount' => null, 'country' => 'SK'])));
        $this->assertSame('PRIV', $carrier->productType($this->request(['codAmount' => null])));
        app(PplSettings::class)->fill(['business' => true])->save();
        $this->assertSame('BUSD', app(PplCarrier::class)->productType($this->request()));
        $this->assertSame(['parcelShopCode' => 'KM12345'], $carrier->shipment($this->request(['pickupPoint' => ['code' => 'KM12345']]))['specificDelivery']);

        $this->fakeApi();
        $tracking = $carrier->track('b-77', '40990012345');
        $this->assertSame(ShipmentStatus::ReadyForPickup, $tracking->status);
        $this->assertSame('Zásilka je připravena k vyzvednutí', $tracking->text);
        $this->assertSame(ShipmentStatus::Delivered, PplCarrier::status('Delivered'));
        $this->assertSame(ShipmentStatus::Returned, PplCarrier::status('BackToSender'));
        $this->assertSame('https://www.ppl.cz/vyhledat-zasilku?shipmentId=40990012345', $carrier->trackingUrl('40990012345'));

        // Chyba importu v dávke.
        Http::swap(new Factory);
        $this->fakeApi(['Error']);

        try {
            $carrier->createShipment($this->request());
            $this->fail('Chybná zásielka mala vyhodiť výnimku.');
        } catch (CarrierException $e) {
            $this->assertSame(CarrierException::INVALID_DATA, $e->type);
            $this->assertStringContainsString('Neplatné PSČ', $e->getMessage());
        }

        // Validačná chyba pri odoslaní dávky.
        Http::swap(new Factory);
        Http::fake([
            '*/getAccessToken' => Http::response(['access_token' => 'token-1']),
            '*' => Http::response(['title' => 'Validation', 'errors' => ['shipments[0].recipient.zipCode' => ['Neplatné PSČ.']]], 400),
        ]);

        try {
            $carrier->createShipment($this->request());
            $this->fail('Validačná chyba mala vyhodiť výnimku.');
        } catch (CarrierException $e) {
            $this->assertSame('PPL: recipient.zipCode: Neplatné PSČ.', $e->getMessage());
        }
    }

    public function test_slow_batch_is_finished_later_by_tracking_and_labels(): void
    {
        $carrier = $this->carrier();
        $this->fakeApi(array_merge(array_fill(0, PplCarrier::BATCH_ATTEMPTS, 'InProcess'), ['Complete']));

        $result = $carrier->createShipment($this->request());
        $this->assertSame('b-77', $result->reference);
        $this->assertNull($result->trackingNumber);
        $this->assertNull($result->label);

        // Pri sledovaní sa číslo zásielky zistí z dávky.
        $this->assertSame(ShipmentStatus::ReadyForPickup, $carrier->track('b-77')->status);
        $this->assertSame('%PDF-all', $carrier->labels(['b-77']));
    }

    public function test_access_points_sync_and_settings_page(): void
    {
        $carrier = $this->carrier();
        Http::fake([
            '*/getAccessToken' => Http::response(['access_token' => 'token-1']),
            '*/accessPoint*' => Http::response([
                ['accessPointCode' => 'KM100', 'accessPointType' => 'ParcelShop', 'name' => 'Trafika U Nádraží', 'street' => 'Nádražní 1', 'city' => 'Brno', 'zipCode' => '60200', 'country' => 'CZ',
                    'gps' => ['latitude' => 49.19, 'longitude' => 16.61], 'workHours' => [['weekDay' => 1, 'dayPart' => 1, 'openFrom' => '08:00:00', 'openTo' => '12:00:00'], ['weekDay' => 1, 'dayPart' => 2, 'openFrom' => '13:00:00', 'openTo' => '17:00:00']]],
                ['accessPointCode' => 'PB200', 'accessPointType' => 'ParcelBox', 'name' => 'PPL Parcelbox Albert', 'city' => 'Brno', 'zipCode' => '60300', 'country' => 'CZ'],
            ]),
        ]);

        $this->assertSame(2, $carrier->syncPickupPoints());
        $shop = PickupPoint::query()->where('carrier', 'ppl')->where('code', 'KM100')->firstOrFail();
        $this->assertSame('parcelshop', $shop->type);
        $this->assertSame('Po 08:00-12:00, 13:00-17:00', $shop->opening_hours);
        $this->assertSame('parcelbox', PickupPoint::query()->where('code', 'PB200')->value('type'));
        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'accessPoint?') && str_contains($request->url(), 'CountryCode=CZ') && str_contains($request->url(), 'Limit=1000'));

        Http::swap(new Factory);
        Http::fake(['*' => Http::response(['error' => 'invalid_client'], 400)]);
        Cache::flush();

        try {
            $carrier->testConnection();
            $this->fail('Nesprávne údaje mali vyhodiť výnimku.');
        } catch (CarrierException $e) {
            $this->assertSame(CarrierException::CONFIGURATION, $e->type);
        }

        $this->actingAs($this->superAdmin());
        $this->get('/admin/settings/ppl')->assertOk()->assertSee('myAPI2');
        Livewire::test(ManagePplSettings::class)->fillForm(['business' => true, 'label_page' => 'Default'])->call('save')->assertHasNoFormErrors();
        $this->assertSame('Default', app(PplSettings::class)->refresh()->label_page);
        $this->assertSame('tajne-heslo-123', app(PplSettings::class)->client_secret);
    }
}
