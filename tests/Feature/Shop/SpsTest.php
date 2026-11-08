<?php

namespace Tests\Feature\Shop;

use App\Settings\CompanySettings;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Modules\Shipping\Carriers\CarrierException;
use Modules\Shipping\Carriers\ShipmentRequest;
use Modules\Shipping\Carriers\ShipmentStatus;
use Modules\Shipping\ShippingType;
use Modules\Sps\Filament\Pages\ManageSpsSettings;
use Modules\Sps\SpsApi;
use Modules\Sps\SpsCarrier;
use Modules\Sps\SpsSettings;

class SpsTest extends ShopTestCase
{
    private function carrier(): SpsCarrier
    {
        $settings = app(SpsSettings::class);
        $settings->username = 'obchod';
        $settings->password = 'tajne & heslo';
        $settings->save();

        app(CompanySettings::class)->fill(['iban' => 'SK31 1200 0000 1987 4263 7541'])->save();

        return app(SpsCarrier::class);
    }

    private function request(array $overrides = []): ShipmentRequest
    {
        return new ShipmentRequest(...[
            'reference' => '2026000012', 'firstName' => 'Jana', 'lastName' => 'Nováková', 'company' => null,
            'email' => 'jana@example.com', 'phone' => '+421900123456', 'street' => 'Hlavná 12', 'city' => 'Žilina',
            'zip' => '010 01', 'country' => 'SK', 'pickupPoint' => null, 'codAmount' => 2590, 'currency' => 'EUR',
            'value' => 2590, 'weightGrams' => 1250, 'methodOptions' => [], ...$overrides,
        ]);
    }

    private static function soap(string $operation, string $inner): string
    {
        return '<?xml version="1.0" encoding="utf-8"?><soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            .'<soapenv:Body><'.$operation.'Response xmlns="http://server.webship.solver.sk"><'.$operation.'Return>'.$inner.'</'.$operation.'Return></'.$operation.'Response></soapenv:Body></soapenv:Envelope>';
    }

    public function test_creates_shipment_with_label_and_tracks_by_reference(): void
    {
        $carrier = $this->carrier();
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), 'labels.sps')) {
                return Http::response('%PDF-sps');
            }

            return str_contains($request->body(), 'createAndPrintCifShipment')
                ? Http::response(self::soap('createAndPrintCifShipment', '<packageInfo><item><packageNo>1</packageNo><refNr>2026000012</refNr><shipNr>703012345</shipNr><trackingUrl>https://t-t.sps-sro.sk/x</trackingUrl></item></packageInfo><result><errors></errors><warnings>Pozor</warnings></result><documentUrl>https://labels.sps/doc-1.pdf</documentUrl>'))
                : Http::response(self::soap('getShipmentStatusesByReference', '<errors/><events>'
                    .'<item><date>09.11.2026</date><time>08:00</time><status>Zásielka prevzatá od odosielateľa</status></item>'
                    .'<item><date>10.11.2026</date><time>14:30</time><status>Zásielka doručená</status></item>'
                    .'<item><date>09.11.2026</date><time>18:00</time><status>Zásielka na triediacom centre</status></item>'
                    .'</events>'));
        });

        $result = $carrier->createShipment($this->request());

        $this->assertSame('2026000012', $result->reference);
        $this->assertSame('703012345', $result->trackingNumber);
        $this->assertSame('%PDF-sps', $result->label);

        Http::assertSent(function (Request $request) {
            $body = $request->body();

            return $request->url() === SpsApi::URL
                && str_contains($body, '<s:createAndPrintCifShipment><s:name>obchod</s:name><s:password>tajne &amp; heslo</s:password>')
                && str_contains($body, '<c:cod><c:codretbankacc>SK3112000000198742637541</c:codretbankacc><c:codvalue>25.90</c:codvalue><c:currency>EUR</c:currency></c:cod>')
                && str_contains($body, '<c:packages><s:item><c:reffnr>2026000012</c:reffnr><c:weight>1.25</c:weight></s:item></c:packages>')
                && str_contains($body, '<c:zip>01001</c:zip>')
                && str_contains($body, '<c:notifytype>3</c:notifytype>')
                && str_contains($body, '<c:recipientpay>false</c:recipientpay>')
                && str_contains($body, '<c:deliverytype>2PT</c:deliverytype>');
        });

        $tracking = $carrier->track('2026000012', '703012345');
        $this->assertSame(ShipmentStatus::Delivered, $tracking->status);
        $this->assertSame('Zásielka doručená', $tracking->text);
        Http::assertSent(fn (Request $request) => str_contains($request->body(), '<s:reffnr>2026000012</s:reffnr>'));
        $this->assertSame('https://www.sps-sro.sk/moja-zasielka/?shipment_id=703012345', $carrier->trackingUrl('703012345'));
        $this->assertFalse($carrier->supports(ShippingType::PickupPoint));
    }

    public function test_errors_and_settings_page(): void
    {
        $carrier = $this->carrier();
        Http::fake(['*' => Http::response(self::soap('createAndPrintCifShipment', '<result><errors>Neplatné PSČ príjemcu</errors></result>'))]);

        try {
            $carrier->createShipment($this->request());
            $this->fail('Chyba mala vyhodiť výnimku.');
        } catch (CarrierException $e) {
            $this->assertSame(CarrierException::INVALID_DATA, $e->type);
            $this->assertSame('SPS: Neplatné PSČ príjemcu', $e->getMessage());
        }

        Http::swap(new Factory);
        Http::fake(['*' => Http::response('<?xml version="1.0"?><soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"><soapenv:Body><soapenv:Fault><faultcode>soapenv:Server</faultcode><faultstring>Invalid login</faultstring></soapenv:Fault></soapenv:Body></soapenv:Envelope>', 500)]);

        try {
            $carrier->testConnection();
            $this->fail('Chyba mala vyhodiť výnimku.');
        } catch (CarrierException $e) {
            $this->assertSame('SPS: Invalid login', $e->getMessage());
        }

        try {
            $carrier->labels(['2026000012']);
            $this->fail('Opätovná tlač mala vyhodiť výnimku.');
        } catch (CarrierException $e) {
            $this->assertStringContainsString('WebShip', $e->getMessage());
        }

        $this->actingAs($this->superAdmin());
        $this->get('/admin/settings/sps')->assertOk()->assertSee('webship.sps-sro.sk');
        Livewire::test(ManageSpsSettings::class)->fillForm(['notify' => 1, 'service' => 'expres'])->call('save')->assertHasNoFormErrors();
        $this->assertSame(1, app(SpsSettings::class)->refresh()->notify);
        $this->assertSame('tajne & heslo', app(SpsSettings::class)->password);
    }
}
