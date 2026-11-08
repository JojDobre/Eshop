<?php

namespace Tests\Feature\Shop;

use App\Settings\CompanySettings;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Modules\CeskaPosta\CeskaPostaApi;
use Modules\CeskaPosta\CeskaPostaCarrier;
use Modules\CeskaPosta\CeskaPostaSettings;
use Modules\CeskaPosta\Filament\Pages\ManageCeskaPostaSettings;
use Modules\Shipping\Carriers\CarrierException;
use Modules\Shipping\Carriers\CarrierRegistry;
use Modules\Shipping\Carriers\ShipmentRequest;
use Modules\Shipping\Carriers\ShipmentStatus;
use Modules\Shipping\Models\PickupPoint;

class CeskaPostaTest extends ShopTestCase
{
    private function carrier(): CeskaPostaCarrier
    {
        $settings = app(CeskaPostaSettings::class);
        $settings->api_token = '123e4567-e89b-12d3-a456-426614174000';
        $settings->secret_key = 'c2VjcmV0LWtsdWM=';
        $settings->customer_id = 'M12345';
        $settings->post_code = '110 00';
        $settings->location_number = 1;
        $settings->save();

        $company = app(CompanySettings::class);
        $company->company_name = 'Obchod s.r.o.';
        $company->street = 'Dlouhá 15';
        $company->city = 'Praha';
        $company->zip = '110 00';
        $company->country = 'CZ';
        $company->save();

        return app(CeskaPostaCarrier::class);
    }

    private function request(array $overrides = []): ShipmentRequest
    {
        return new ShipmentRequest(...[
            'reference' => '2026000012', 'firstName' => 'Jana', 'lastName' => 'Nováková', 'company' => null,
            'email' => 'jana@example.com', 'phone' => '+420 777 123 456', 'street' => 'Masarykova 12/A', 'city' => 'Brno',
            'zip' => '602 00', 'country' => 'CZ', 'pickupPoint' => null, 'codAmount' => 59000, 'currency' => 'CZK',
            'value' => 59000, 'weightGrams' => 1250, 'methodOptions' => [], ...$overrides,
        ]);
    }

    private static function parcelResponse(string $code, ?string $label = '%PDF-label'): array
    {
        return ['responseHeader' => [
            'resultHeader' => ['responseCode' => 1, 'responseText' => 'OK'],
            'resultParcelData' => [['recordNumber' => '1', 'parcelCode' => $code, 'parcelStateResponse' => [['responseCode' => 1, 'responseText' => 'OK']]]],
            'responsePrintParams' => ['file' => $label !== null ? base64_encode($label) : null],
        ]];
    }

    public function test_requests_are_signed_with_hmac(): void
    {
        $this->carrier();
        $headers = app(CeskaPostaApi::class)->signedHeaders('{"a":1}', 1700000000, 'f47ac10b-58cc-4372-a567-0e02b2c3d479');

        $hash = hash('sha256', '{"a":1}');
        $expected = base64_encode(hash_hmac('sha256', $hash.';1700000000;f47ac10b-58cc-4372-a567-0e02b2c3d479', 'c2VjcmV0LWtsdWM=', true));

        $this->assertSame('123e4567-e89b-12d3-a456-426614174000', $headers['Api-Token']);
        $this->assertSame('1700000000', $headers['Authorization-Timestamp']);
        $this->assertSame($hash, $headers['Authorization-Content-SHA256']);
        $this->assertSame('CP-HMAC-SHA256 nonce="f47ac10b-58cc-4372-a567-0e02b2c3d479" signature="'.$expected.'"', $headers['Authorization']);

        // Požiadavka bez tela: podpisuje sa „;časováPečiatka;nonce“ a hlavička s hashom sa neposiela.
        $get = app(CeskaPostaApi::class)->signedHeaders('', 1700000000, 'n');
        $this->assertArrayNotHasKey('Authorization-Content-SHA256', $get);
        $this->assertStringContainsString(base64_encode(hash_hmac('sha256', ';1700000000;n', 'c2VjcmV0LWtsdWM=', true)), $get['Authorization']);
    }

    public function test_home_delivery_with_cod_and_label(): void
    {
        $carrier = $this->carrier();
        Http::fake(['b2b.postaonline.cz:444/*' => Http::response(self::parcelResponse('DR1234567890U'))]);

        $result = $carrier->createShipment($this->request());

        $this->assertSame('DR1234567890U', $result->reference);
        $this->assertSame('DR1234567890U', $result->trackingNumber);
        $this->assertSame('%PDF-label', $result->label);

        Http::assertSent(function (Request $request) {
            $data = $request->data();
            $params = $data['parcelServiceData']['parcelParams'];
            $address = $data['parcelServiceData']['parcelAddress'];
            $header = $data['parcelServiceHeader'];

            // Podpis musí sedieť na presne odoslané telo.
            $this->assertSame(hash('sha256', $request->body()), $request->header('Authorization-Content-SHA256')[0]);
            $this->assertStringEndsWith('/ZSKService/v1/parcelService', $request->url());

            return $params['prefixParcelCode'] === 'DR' && $params['weight'] === '1.250' && $params['amount'] == 590
                && $params['currency'] === 'CZK' && $params['vsVoucher'] === '2026000012'
                && $data['parcelServiceData']['parcelServices'] === ['S', '41']
                && $address['address'] === ['street' => 'Masarykova', 'houseNumber' => '12/A', 'city' => 'Brno', 'zipCode' => '60200', 'isoCountry' => 'CZ']
                && $address['mobilNumber'] === '777123456'
                && $header['parcelServiceHeaderCom']['customerID'] === 'M12345' && $header['parcelServiceHeaderCom']['postCode'] === '11000'
                && $header['senderAddress']['street'] === 'Dlouhá' && $header['senderAddress']['houseNumber'] === '15'
                && $header['printParams']['idForm'] === 101;
        });
    }

    public function test_balikovna_labels_tracking_and_errors(): void
    {
        $carrier = $this->carrier();
        Http::fake([
            '*/parcelService' => Http::response(self::parcelResponse('NB0600004030U', null)),
            '*/parcelPrinting' => Http::response(['printingHeaderResult' => ['printingStatusResponse' => ['responseCode' => 1, 'responseText' => 'OK']], 'printingDataResult' => base64_encode('%PDF-all')]),
            '*/parcelStatuses/*' => Http::response(['idParcel' => 'NB0600004030U', 'parcelStatus' => ['statusID' => '82', 'statusDescription' => 'Zásilka je připravena k vyzvednutí.']]),
        ]);

        $result = $carrier->createShipment($this->request(['pickupPoint' => ['code' => '10008', 'name' => 'Praha 10 AlzaBox Šrobárova'], 'codAmount' => null]));
        $this->assertNull($result->label);
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), 'parcelService')
            && $request->data()['parcelServiceData']['parcelParams']['prefixParcelCode'] === 'NB'
            && $request->data()['parcelServiceData']['parcelAddress']['address'] === ['street' => 'Balíkovna', 'city' => 'Praha 10 AlzaBox Šrobárova', 'zipCode' => '10008', 'isoCountry' => 'CZ']
            && $request->data()['parcelServiceData']['parcelServices'] === ['S']);

        $this->assertSame('%PDF-all', $carrier->labels(['NB0600004030U', 'DR1234567890U']));
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), 'parcelPrinting') && $request->data()['printingData'] === ['NB0600004030U', 'DR1234567890U']);

        $tracking = $carrier->track('NB0600004030U', 'NB0600004030U');
        $this->assertSame(ShipmentStatus::ReadyForPickup, $tracking->status);
        $this->assertSame('Zásilka je připravena k vyzvednutí.', $tracking->text);
        Http::assertSent(fn (Request $request) => $request->method() === 'GET' && ! $request->hasHeader('Authorization-Content-SHA256'));

        $this->assertSame(ShipmentStatus::Delivered, CeskaPostaCarrier::status('91', 'Dodání zásilky.'));
        $this->assertSame(ShipmentStatus::Created, CeskaPostaCarrier::status('-B', 'Obdrženy údaje k zásilce.'));
        $this->assertSame(ShipmentStatus::Returned, CeskaPostaCarrier::status('68', 'Vrácení zásilky odesílateli.'));
        $this->assertSame('https://www.postaonline.cz/trackandtrace/-/zasilka/cislo?parcelNumbers=DR1', $carrier->trackingUrl('DR1'));

        Http::swap(new Factory);
        Http::fake(['*' => Http::response(['responseHeader' => ['resultHeader' => ['responseCode' => 1, 'responseText' => 'OK'], 'resultParcelData' => [['parcelStateResponse' => [['responseCode' => 102, 'responseText' => 'INVALID_ZIP_CODE']]]]]])]);

        try {
            $carrier->createShipment($this->request());
            $this->fail('Chybná adresa mala vyhodiť výnimku.');
        } catch (CarrierException $e) {
            $this->assertSame(CarrierException::INVALID_DATA, $e->type);
            $this->assertStringContainsString('INVALID_ZIP_CODE', $e->getMessage());
        }
    }

    public function test_pickup_points_sync_from_branch_list(): void
    {
        $this->carrier();
        $row = fn (string $psc, string $name, string $type, string $address) => "<row><PSC>{$psc}</PSC><NAZEV>{$name}</NAZEV><ADRESA>{$address}</ADRESA><TYP>{$type}</TYP>"
            .'<OTEV_DOBY><den name="Pondělí"><od_do><od>08:00</od><do>12:00</do></od_do><od_do><od>13:00</od><do>18:00</do></od_do></den><den name="Neděle"/></OTEV_DOBY>'
            .'<OBEC>Praha</OBEC><SOUR_X_WGS84>14.49</SOUR_X_WGS84><SOUR_Y_WGS84>50.07</SOUR_Y_WGS84></row>';
        $xml = '<?xml version="1.0" encoding="UTF-8"?><zv xmlns="http://www.cpost.cz/schema/aict/zv_2"><generated>2026-11-07T17:52:14</generated>'
            .$row('10000', 'Praha 10', 'pošta', 'Černokostelecká 2020/20, Strašnice, 10000, Praha 10')
            .$row('10008', 'Praha 10 AlzaBox', 'balíkovna-BOX', 'Šrobárova , Vinohrady, 10000, Praha 10')
            .$row('19900', 'Depo Praha', 'depo', 'Sazečská 1, Malešice, 10800, Praha 10')
            .'</zv>';
        Http::fake(['napostu.ceskaposta.cz/*' => Http::response($xml)]);

        $this->assertSame(2, app(CarrierRegistry::class)->find('ceskaposta')->syncPickupPoints());

        $office = PickupPoint::query()->where('carrier', 'ceskaposta')->where('code', '10000')->firstOrFail();
        $this->assertSame('post_office', $office->type);
        $this->assertSame('Černokostelecká 2020/20', $office->street);
        $this->assertSame('Po 08:00-12:00, 13:00-18:00', $office->opening_hours);
        $this->assertEqualsWithDelta(50.07, (float) $office->latitude, 0.001);
        $this->assertSame('box', PickupPoint::query()->where('code', '10008')->value('type'));
        $this->assertFileDoesNotExist(storage_path('app/eshop/tmp/ceskaposta-points.xml'));
    }

    public function test_credentials_and_settings_page(): void
    {
        $this->assertFalse(app(CeskaPostaCarrier::class)->isConfigured());

        try {
            app(CeskaPostaCarrier::class)->testConnection();
            $this->fail('Chýbajúce kľúče mali vyhodiť výnimku.');
        } catch (CarrierException $e) {
            $this->assertSame(CarrierException::CONFIGURATION, $e->type);
        }

        $carrier = $this->carrier();
        $this->assertTrue($carrier->isConfigured());

        // Neexistujúca zásielka (404) znamená, že podpis prešiel.
        Http::fake(['*' => Http::response(['code' => -1, 'message' => 'Not found'], 404)]);
        $carrier->testConnection();

        Http::swap(new Factory);
        Http::fake(['*' => Http::response('', 401)]);

        try {
            $carrier->testConnection();
            $this->fail('Nesprávne kľúče mali vyhodiť výnimku.');
        } catch (CarrierException $e) {
            $this->assertSame(CarrierException::CONFIGURATION, $e->type);
        }

        $this->actingAs($this->superAdmin());
        $this->get('/admin/settings/ceskaposta')->assertOk()->assertSee('Podání Online');
        Livewire::test(ManageCeskaPostaSettings::class)
            ->fillForm(['label_form' => 200, 'parcel_size' => null, 'test_mode' => true])
            ->call('save')->assertHasNoFormErrors();

        $settings = app(CeskaPostaSettings::class)->refresh();
        $this->assertSame(200, $settings->label_form);
        $this->assertNull($settings->parcel_size);
        $this->assertSame(CeskaPostaApi::TEST_URL, app(CeskaPostaApi::class)->baseUrl());
    }
}
