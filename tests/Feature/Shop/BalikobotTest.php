<?php

namespace Tests\Feature\Shop;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Modules\Balikobot\BalikobotCarrier;
use Modules\Balikobot\BalikobotSettings;
use Modules\Shipping\Carriers\CarrierException;
use Modules\Shipping\Carriers\ShipmentRequest;
use Modules\Shipping\Carriers\ShipmentStatus;
use Modules\Shipping\Models\PickupPoint;
use Modules\Shipping\Models\ShippingMethod;
use Modules\Shipping\ShippingType;

class BalikobotTest extends ShopTestCase
{
    private function carrier(): BalikobotCarrier
    {
        $settings = app(BalikobotSettings::class);
        $settings->api_user = 'obchod';
        $settings->api_key = 'kluc-123';
        $settings->save();

        return app(BalikobotCarrier::class);
    }

    private function request(array $overrides = []): ShipmentRequest
    {
        return new ShipmentRequest(...[
            'reference' => '2026000012', 'firstName' => 'Jana', 'lastName' => 'Nováková', 'company' => null,
            'email' => 'jana@example.com', 'phone' => '+420777123456', 'street' => 'Masarykova 12', 'city' => 'Brno',
            'zip' => '602 00', 'country' => 'CZ', 'pickupPoint' => null, 'codAmount' => 59000, 'currency' => 'CZK',
            'value' => 59000, 'weightGrams' => 1250, 'methodOptions' => ['shipper' => 'dpd', 'service_type' => '1'], ...$overrides,
        ]);
    }

    public function test_creates_and_orders_shipment_prints_labels_and_tracks(): void
    {
        $carrier = $this->carrier();
        Http::fake([
            'apiv2.balikobot.cz/dpd/add' => Http::response(['status' => 200, 'packages' => [['eid' => '2026000012', 'package_id' => 4242, 'carrier_id' => '13912345678', 'label_url' => 'https://pdf.balikobot.cz/l1', 'status' => 200]]]),
            'apiv2.balikobot.cz/dpd/order' => Http::response(['status' => 200, 'order_id' => 1]),
            'apiv2.balikobot.cz/dpd/labels' => Http::response(['status' => 200, 'labels_url' => 'https://pdf.balikobot.cz/all']),
            'apiv2.balikobot.cz/dpd/trackstatus' => Http::response(['status' => 200, 'packages' => [['carrier_id' => '13912345678', 'status_id' => 2.3, 'status_text' => 'Připraveno k vyzvednutí']]]),
            'pdf.balikobot.cz/l1' => Http::response('%PDF-one'),
            'pdf.balikobot.cz/all' => Http::response('%PDF-all'),
        ]);

        $result = $carrier->createShipment($this->request());

        $this->assertSame('dpd:4242', $result->reference);
        $this->assertSame('13912345678', $result->trackingNumber);
        $this->assertSame('%PDF-one', $result->label);

        Http::assertSent(function (Request $request) {
            if (! str_ends_with($request->url(), '/dpd/add')) {
                return false;
            }

            $package = $request['packages'][0];

            return $request->header('Authorization')[0] === 'Basic '.base64_encode('obchod:kluc-123')
                && $package['service_type'] === '1' && $package['rec_zip'] === '60200' && $package['cod_price'] == 590
                && $package['cod_currency'] === 'CZK' && $package['vs'] === '2026000012' && ! isset($package['branch_id']);
        });
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/dpd/order') && $request['package_ids'] === [4242]);

        $this->assertSame('%PDF-all', $carrier->labels(['dpd:4242', 'dpd:4243']));
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/dpd/labels') && $request['package_ids'] === ['4242', '4243']);

        $tracking = $carrier->track('dpd:4242', '13912345678');
        $this->assertSame(ShipmentStatus::ReadyForPickup, $tracking->status);
        $this->assertSame('Připraveno k vyzvednutí', $tracking->text);
        $this->assertSame(ShipmentStatus::Delivered, BalikobotCarrier::status(1.2));
        $this->assertSame(ShipmentStatus::Returned, BalikobotCarrier::status(4.0));
        $this->assertSame(ShipmentStatus::Cancelled, BalikobotCarrier::status(3.3));
        $this->assertSame('https://tracking.dpd.de/status/cs_CZ/parcel/13912345678', $carrier->trackingUrl('13912345678', ['shipper' => 'dpd']));
        $this->assertNull($carrier->trackingUrl('1', ['shipper' => 'geis']));

        try {
            $carrier->labels(['dpd:1', 'geis:2']);
            $this->fail('Štítky dvoch kuriérov mali vyhodiť výnimku.');
        } catch (CarrierException $e) {
            $this->assertSame(CarrierException::INVALID_DATA, $e->type);
        }
    }

    public function test_pickup_points_errors_and_settings(): void
    {
        $carrier = $this->carrier();
        $settings = app(BalikobotSettings::class);
        $settings->auto_order = false;
        $settings->save();

        ShippingMethod::query()->create(['name' => 'Geis Point', 'type' => ShippingType::PickupPoint, 'price' => 0, 'is_active' => true, 'carrier' => 'balikobot', 'carrier_options' => ['shipper' => 'geis', 'service_type' => '6', 'point_types' => ['geis:6']]]);
        Http::fake([
            'apiv2.balikobot.cz/geis/branches/service/6' => Http::response(['status' => 200, 'branches' => [
                ['branch_id' => 'G100', 'name' => 'Geis Point Trafika', 'street' => 'Hlavní 1', 'city' => 'Brno', 'zip' => '60200', 'country' => 'CZ', 'latitude' => '49.19', 'longitude' => '16.61'],
            ]]),
            'apiv2.balikobot.cz/geis/add' => Http::response(['status' => 200, 'packages' => [['package_id' => 7, 'carrier_id' => 'G7', 'status' => 200]]]),
        ]);

        $this->assertSame(1, $carrier->syncPickupPoints());
        $point = PickupPoint::query()->where('carrier', 'balikobot')->sole();
        $this->assertSame('geis:G100', $point->code);
        $this->assertSame('geis:6', $point->type);

        $result = $carrier->createShipment($this->request(['methodOptions' => ['shipper' => 'geis', 'service_type' => '6'], 'pickupPoint' => ['code' => 'geis:G100'], 'codAmount' => null]));
        $this->assertSame('geis:7', $result->reference);
        $this->assertNull($result->label);
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/geis/add') && $request['packages'][0]['branch_id'] === 'G100');
        Http::assertNotSent(fn (Request $request) => str_ends_with($request->url(), '/order'));

        Http::swap(new Factory);
        Http::fake(['*' => Http::response(['status' => 400, 'packages' => [['status' => 413, 'errors' => ['rec_zip' => 'Neplatné PSČ']]]], 200)]);

        try {
            $carrier->createShipment($this->request());
            $this->fail('Chyba mala vyhodiť výnimku.');
        } catch (CarrierException $e) {
            $this->assertSame('Balíkobot: rec_zip: Neplatné PSČ', $e->getMessage());
        }

        Http::swap(new Factory);
        Http::fake(['*' => Http::response(['status' => 401], 401)]);

        try {
            $carrier->testConnection();
            $this->fail('Nesprávne údaje mali vyhodiť výnimku.');
        } catch (CarrierException $e) {
            $this->assertSame(CarrierException::CONFIGURATION, $e->type);
        }

        $this->actingAs($this->superAdmin());
        $this->get('/admin/settings/balikobot')->assertOk()->assertSee('Balíkobot');
    }
}
