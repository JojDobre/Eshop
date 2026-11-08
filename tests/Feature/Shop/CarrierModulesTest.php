<?php

namespace Tests\Feature\Shop;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Modules\Dpd\DpdCarrier;
use Modules\Gls\Filament\Pages\ManageGlsSettings;
use Modules\Gls\GlsCarrier;
use Modules\Gls\GlsSettings;
use Modules\Packeta\PacketaCarrier;
use Modules\Packeta\PacketaSettings;
use Modules\Shipping\Carriers\CarrierException;
use Modules\Shipping\Carriers\CarrierRegistry;
use Modules\Shipping\Carriers\ShipmentRequest;
use Modules\Shipping\Carriers\ShipmentStatus;
use Modules\Shipping\Models\PickupPoint;
use Modules\Shipping\Models\ShippingMethod;
use Modules\Shipping\ShippingType;

class CarrierModulesTest extends ShopTestCase
{
    private function request(array $overrides = []): ShipmentRequest
    {
        return new ShipmentRequest(...[
            'reference' => '2026000012', 'firstName' => 'Jana', 'lastName' => 'Nováková', 'company' => null,
            'email' => 'jana@example.com', 'phone' => '+421900123456', 'street' => 'Hlavná 12/A', 'city' => 'Bratislava',
            'zip' => '811 01', 'country' => 'SK', 'pickupPoint' => null, 'codAmount' => 2590, 'currency' => 'EUR',
            'value' => 2590, 'weightGrams' => 1250, 'methodOptions' => [], ...$overrides,
        ]);
    }

    private function packeta(): PacketaCarrier
    {
        $settings = app(PacketaSettings::class);
        $settings->api_key = 'abcdefgh12345678';
        $settings->api_password = str_repeat('a1', 16);
        $settings->sender = 'mojobchod';
        $settings->save();

        return app(PacketaCarrier::class);
    }

    private function gls(): GlsCarrier
    {
        $settings = app(GlsSettings::class);
        $settings->username = 'api@obchod.sk';
        $settings->password = 'tajne-heslo';
        $settings->client_number = '100000001';
        $settings->save();

        return app(GlsCarrier::class);
    }

    public function test_carriers_are_registered(): void
    {
        $registry = app(CarrierRegistry::class);

        $this->assertInstanceOf(PacketaCarrier::class, $registry->find('packeta'));
        $this->assertInstanceOf(GlsCarrier::class, $registry->find('gls'));
        $this->assertInstanceOf(DpdCarrier::class, $registry->find('dpd'));
        $this->assertSame('https://tracking.dpd.de/status/sk_SK/parcel/01234567890123', $registry->find('dpd')->trackingUrl('01234567890123'));
    }

    public function test_packeta_creates_pickup_point_packet_with_label(): void
    {
        $carrier = $this->packeta();
        Http::fake(['www.zasilkovna.cz/*' => function (Request $request) {
            return str_contains($request->body(), '<createPacket>')
                ? Http::response('<?xml version="1.0"?><response><status>ok</status><result><id>4154010000</id><barcode>Z4154010000</barcode><barcodeText>Z 415 4010 000</barcodeText></result></response>')
                : Http::response('<?xml version="1.0"?><response><status>ok</status><result>'.base64_encode('%PDF-label').'</result></response>');
        }]);

        $result = $carrier->createShipment($this->request(['pickupPoint' => ['code' => '79', 'name' => 'Trafika']]));

        $this->assertSame('4154010000', $result->reference);
        $this->assertSame('Z4154010000', $result->trackingNumber);
        $this->assertSame('%PDF-label', $result->label);

        Http::assertSent(function (Request $request) {
            $xml = simplexml_load_string($request->body());

            return $request->url() === 'https://www.zasilkovna.cz/api/rest' && $xml->getName() === 'createPacket'
                && (string) $xml->apiPassword === str_repeat('a1', 16)
                && (string) $xml->packetAttributes->addressId === '79'
                && (string) $xml->packetAttributes->number === '2026000012'
                && (string) $xml->packetAttributes->cod === '25.90'
                && (string) $xml->packetAttributes->weight === '1.250'
                && (string) $xml->packetAttributes->eshop === 'mojobchod'
                && ! isset($xml->packetAttributes->street);
        });
    }

    public function test_packeta_home_delivery_labels_status_and_errors(): void
    {
        $carrier = $this->packeta();
        Http::fake(['www.zasilkovna.cz/*' => function (Request $request) {
            $xml = simplexml_load_string($request->body());

            return match ($xml->getName()) {
                'createPacket' => Http::response('<response><status>fault</status><fault>PacketAttributesFault</fault><string>Invalid packet attributes</string><detail><attributes><fault><name>zip</name><fault>Invalid ZIP.</fault></fault></attributes></detail></response>'),
                'packetsLabelsPdf' => Http::response('<response><status>ok</status><result>'.base64_encode('%PDF-many').'</result></response>'),
                'packetStatus' => Http::response('<response><status>ok</status><result><dateTime>2026-11-08T10:00:00</dateTime><statusCode>7</statusCode><codeText>delivered</codeText><statusText>Zásielka bola vyzdvihnutá.</statusText></result></response>'),
            };
        }]);

        try {
            $carrier->createShipment($this->request(['methodOptions' => ['carrier_id' => '131']]));
            $this->fail('Chyba poľa mala vyhodiť výnimku.');
        } catch (CarrierException $e) {
            $this->assertStringContainsString('zip: Invalid ZIP.', $e->getMessage());
            $this->assertFalse($e->isTemporary());
        }

        Http::assertSent(fn (Request $request) => str_contains($request->body(), '<street>Hlavná</street>') && str_contains($request->body(), '<houseNumber>12/A</houseNumber>') && str_contains($request->body(), '<zip>81101</zip>'));

        $this->assertSame('%PDF-many', $carrier->labels(['1', '2']));
        Http::assertSent(fn (Request $request) => str_contains($request->body(), '<packetIds><id>1</id><id>2</id></packetIds>'));

        $tracking = $carrier->track('4154010000');
        $this->assertSame(ShipmentStatus::Delivered, $tracking->status);
        $this->assertSame('Zásielka bola vyzdvihnutá.', $tracking->text);
        $this->assertSame('120', PacketaCarrier::amount(12000, 'CZK'));
        $this->assertSame(ShipmentStatus::ReadyForPickup, PacketaCarrier::status(5));
    }

    public function test_packeta_widget_adapter_in_checkout(): void
    {
        $this->packeta();
        ShippingMethod::query()->create(['name' => 'Packeta výdajné miesto', 'type' => ShippingType::PickupPoint, 'price' => 249, 'carrier' => 'packeta', 'carrier_options' => ['country' => 'sk', 'point_groups' => ['points']]]);
        $this->post(route('cart.add'), ['product_id' => $this->product('Káva')->id]);

        $this->get(route('checkout.show'))->assertOk()
            ->assertSee(PacketaCarrier::WIDGET_SCRIPT, false)
            ->assertSee('window.eshopPickupWidgets.packeta', false)
            ->assertSee('abcdefgh12345678', false);
    }

    public function test_gls_prints_label_with_parcelshop_and_tracks(): void
    {
        $carrier = $this->gls();
        Http::fake([
            'api.mygls.sk/ParcelService.svc/json/PrintLabels' => Http::response(['Labels' => array_map('ord', str_split('%PDF-gls')), 'PrintLabelsErrorList' => [], 'PrintLabelsInfoList' => [['ClientReference' => '2026000012', 'ParcelId' => 555, 'ParcelNumber' => 90012345678]]]),
            'api.mygls.sk/ParcelService.svc/json/GetParcelStatuses' => Http::response(['ParcelNumber' => 90012345678, 'GetParcelStatusErrors' => [], 'ParcelStatusList' => [
                ['StatusCode' => '1', 'StatusDate' => '/Date(1794100000000+0100)/', 'StatusDescription' => 'Odovzdaná GLS'],
                ['StatusCode' => '5', 'StatusDate' => '/Date(1794200000000+0100)/', 'StatusDescription' => 'Doručená'],
            ]]),
        ]);

        $result = $carrier->createShipment($this->request(['pickupPoint' => ['code' => '2351-CSOMAGPONT', 'name' => 'Shop', 'street' => 'Európa u. 2', 'city' => 'Bratislava', 'zip' => '81101', 'country' => 'SK']]));

        $this->assertSame('555', $result->reference);
        $this->assertSame('90012345678', $result->trackingNumber);
        $this->assertSame('%PDF-gls', $result->label);

        Http::assertSent(function (Request $request) {
            $body = $request->data();
            $parcel = $body['ParcelList'][0] ?? [];

            return $request->url() === 'https://api.mygls.sk/ParcelService.svc/json/PrintLabels'
                && $body['Username'] === 'api@obchod.sk'
                && $body['Password'] === array_values(unpack('C*', hash('sha512', 'tajne-heslo', true)))
                && $body['WebshopEngine'] === 'Eshop'
                && $parcel['ClientNumber'] === 100000001
                && $parcel['CODAmount'] === 25.9
                && $parcel['ServiceList'][0] === ['Code' => 'PSD', 'PSDParameter' => ['StringValue' => '2351-CSOMAGPONT']]
                && $parcel['DeliveryAddress']['ContactEmail'] === 'jana@example.com';
        });

        $tracking = $carrier->track('555', '90012345678');
        $this->assertSame(ShipmentStatus::Delivered, $tracking->status);
        $this->assertSame('Doručená', $tracking->text);
    }

    public function test_gls_errors_and_pickup_point_sync(): void
    {
        $carrier = $this->gls();
        $points = [
            ['Id' => 101, 'LegacyId' => '81101-SHOP1', 'DeliveryPointType' => 1, 'Address' => ['Name' => 'Trafika Centrum', 'Street' => 'Obchodná', 'HouseNumber' => '5', 'City' => 'Bratislava', 'ZipCode' => '81106', 'CountryIsoCode' => 'SK'], 'DeliveryPointOpeningList' => [['DayOfWeek' => 1, 'Open' => '08:00-17:00']]],
            ['Id' => 102, 'LegacyId' => '', 'DeliveryPointType' => 2, 'Address' => ['Name' => 'GLS box Tesco', 'Street' => 'Galvaniho', 'City' => 'Bratislava', 'ZipCode' => '82104', 'CountryIsoCode' => 'SK']],
            ['Id' => 103, 'DeliveryPointType' => 3, 'Address' => ['Name' => 'Depo', 'CountryIsoCode' => 'SK']],
        ];
        Http::fake([
            'api.mygls.sk/MasterDataService.svc/json/GetDeliveryPoints' => Http::response(['ErrorCode' => 0, 'IsChanged' => true, 'Data' => array_map('ord', str_split(gzencode(json_encode($points))))]),
            'api.mygls.sk/ParcelService.svc/json/PrintLabels' => Http::response(['Labels' => [], 'PrintLabelsErrorList' => [['ErrorCode' => 14, 'ErrorDescription' => 'Zip code is not valid']], 'PrintLabelsInfoList' => []]),
        ]);

        $this->assertSame(2, $carrier->syncPickupPoints());
        $this->assertSame('Po 08:00-17:00', PickupPoint::query()->where('code', '81101-SHOP1')->value('opening_hours'));
        $this->assertSame('locker', PickupPoint::query()->where('code', '102')->value('type'));

        try {
            $carrier->createShipment($this->request());
            $this->fail('Chyba GLS mala vyhodiť výnimku.');
        } catch (CarrierException $e) {
            $this->assertStringContainsString('Zip code is not valid', $e->getMessage());
        }

        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), 'PrintLabels') && ($request->data()['ParcelList'][0]['ServiceList'][0]['Code'] ?? null) === 'FDS');
    }

    public function test_gls_wrong_credentials_and_settings_page(): void
    {
        $this->gls();
        Http::fake(['*' => Http::response('', 401)]);

        try {
            app(GlsCarrier::class)->testConnection();
            $this->fail('Nesprávne údaje mali vyhodiť výnimku.');
        } catch (CarrierException $e) {
            $this->assertSame(CarrierException::CONFIGURATION, $e->type);
        }

        $this->actingAs($this->superAdmin());
        $this->get('/admin/settings/gls')->assertOk();
        $this->get('/admin/settings/packeta')->assertOk();
        Livewire::test(ManageGlsSettings::class)->callAction('test')->assertNotified();
    }
}
