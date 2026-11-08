<?php

namespace Tests\Feature\Shop;

use App\Settings\CompanySettings;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Modules\Orders\Actions\CreateShipment;
use Modules\Orders\Actions\PrintShipmentLabels;
use Modules\Orders\Models\Order;
use Modules\Shipping\Carriers\CarrierException;
use Modules\Shipping\Carriers\ShipmentRequest;
use Modules\Shipping\ShippingType;
use Modules\SlovenskaPosta\Filament\Pages\ManageSlovenskaPostaSettings;
use Modules\SlovenskaPosta\SlovenskaPostaCarrier;
use Modules\SlovenskaPosta\SlovenskaPostaSettings;
use ZipArchive;

class SlovenskaPostaTest extends ShopTestCase
{
    private function carrier(): SlovenskaPostaCarrier
    {
        $settings = app(SlovenskaPostaSettings::class);
        $settings->user_id = 'obchod';
        $settings->api_key = 'kluc-123';
        $settings->save();

        $company = app(CompanySettings::class);
        $company->company_name = 'Obchod s.r.o.';
        $company->street = 'Hlavná 1';
        $company->city = 'Bratislava';
        $company->zip = '811 01';
        $company->country = 'SK';
        $company->iban = 'SK31 1200 0000 1987 4263 7541';
        $company->save();

        return app(SlovenskaPostaCarrier::class);
    }

    private function request(array $overrides = []): ShipmentRequest
    {
        return new ShipmentRequest(...[
            'reference' => '2026000012', 'firstName' => 'Jana', 'lastName' => 'Nováková', 'company' => null,
            'email' => 'jana@example.com', 'phone' => '+421900123456', 'street' => 'Hlavná 12/A', 'city' => 'Košice',
            'zip' => '040 01', 'country' => 'SK', 'pickupPoint' => null, 'codAmount' => 2590, 'currency' => 'EUR',
            'value' => 2590, 'weightGrams' => 1250, 'methodOptions' => [], ...$overrides,
        ]);
    }

    /** Odpovede API: hárok 77, zásielky s číslami EB…SK, štítky na stiahnutie. */
    private function fakeApi(): void
    {
        $parcels = 0;
        Http::fake(function (Request $request) use (&$parcels) {
            $url = $request->url();

            return match (true) {
                str_ends_with($url, '/rest/v1/sheets') => Http::response(['status' => 'ok', 'sheet' => ['id' => 77]]),
                str_ends_with($url, '/sheets/77/parcels') => Http::response(['status' => 'ok', 'parcel' => ['id' => 500 + (++$parcels), 'parcel_number' => 'EB00000000'.$parcels.'SK']]),
                (bool) preg_match('#/parcels/(\d+)/labels$#', $url, $m) => Http::response(['status' => 'ok', 'labels' => ['url' => 'https://mojezasielky.posta.sk/labels/'.$m[1].'.pdf']]),
                str_contains($url, '/labels/') => Http::response('%PDF-'.basename($url, '.pdf')),
                str_ends_with($url, '/register') => Http::response(['status' => 'ok', 'sheet' => ['state' => 'registered']]),
                default => Http::response(['status' => 'error'], 404),
            };
        });
    }

    public function test_creates_sheet_parcel_label_and_registers(): void
    {
        $carrier = $this->carrier();
        $this->fakeApi();

        $result = $carrier->createShipment($this->request());

        $this->assertSame('77/501', $result->reference);
        $this->assertSame('EB000000001SK', $result->trackingNumber);
        $this->assertSame('%PDF-501', $result->label);
        $this->assertSame('https://tandt.posta.sk/zasielky/EB000000001SK', $carrier->trackingUrl('EB000000001SK'));
        $this->assertNull($carrier->track('77/501', 'EB000000001SK'));
        $this->assertFalse($carrier->supports(ShippingType::PickupPoint));

        Http::assertSent(fn (Request $request) => $request->method() === 'PUT' && str_ends_with($request->url(), '/sheets')
            && $request->header('x-api-auth')[0] === 'apikey obchod:kluc-123'
            && $request['sheet']['parcel_category'] === 'b' && $request['sheet']['payment_type'] === 'h'
            && $request['sheet']['sender']['zip'] === '81101' && $request['sheet']['sender']['country'] === 'SK');

        Http::assertSent(function (Request $request) {
            if (! str_ends_with($request->url(), '/sheets/77/parcels')) {
                return false;
            }

            $parcel = $request['parcel'];

            return $parcel['recipient'] === ['name' => 'Jana Nováková', 'street' => 'Hlavná 12/A', 'city' => 'Košice', 'zip' => '04001', 'country' => 'SK', 'phone' => '+421900123456', 'email' => 'jana@example.com']
                && $parcel['weight'] == 1.25 && $parcel['id'] === '2026000012'
                && $parcel['cod'] === ['type' => 'bdnu', 'amount' => ['value' => 25.9, 'currency' => 'EUR'], 'iban' => 'SK3112000000198742637541', 'symbol' => '2026000012']
                && ! isset($parcel['insurance']);
        });

        Http::assertSent(fn (Request $request) => $request->method() === 'POST' && str_ends_with($request->url(), '/sheets/77/register'));
    }

    public function test_draft_sheet_insurance_and_errors(): void
    {
        $carrier = $this->carrier();
        $settings = app(SlovenskaPostaSettings::class);
        $settings->auto_register = false;
        $settings->insure = true;
        $settings->save();
        $this->fakeApi();

        $carrier->createShipment($this->request(['codAmount' => null]));
        Http::assertNotSent(fn (Request $request) => str_ends_with($request->url(), '/register'));
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/parcels') && $request['parcel']['insurance'] === ['value' => 25.9, 'currency' => 'EUR'] && ! isset($request['parcel']['cod']));

        Http::swap(new Factory);
        Http::fake(['*' => Http::response(['status' => 'error', 'validation_errors' => [['attribute' => 'recipient.zip', 'reason' => 'invalid']]], 422)]);

        try {
            $carrier->createShipment($this->request());
            $this->fail('Chybné PSČ malo vyhodiť výnimku.');
        } catch (CarrierException $e) {
            $this->assertSame(CarrierException::INVALID_DATA, $e->type);
            $this->assertStringContainsString('recipient.zip (invalid)', $e->getMessage());
        }

        // Dobierka bez IBAN sa ani neodošle.
        app(CompanySettings::class)->fill(['iban' => null])->save();

        try {
            $carrier->parcel($this->request());
            $this->fail('Dobierka bez IBAN mala vyhodiť výnimku.');
        } catch (CarrierException $e) {
            $this->assertSame(CarrierException::CONFIGURATION, $e->type);
        }
    }

    public function test_bulk_print_packs_each_label_separately(): void
    {
        $this->carrier();
        $this->fakeApi();
        $this->courier()->update(['carrier' => 'slovenskaposta']);

        foreach (['Granule', 'Miska'] as $name) {
            $this->app['session']->flush();
            $this->post(route('cart.add'), ['product_id' => $this->product($name)->id]);
            $this->post(route('checkout.store'), $this->checkoutData())->assertSessionDoesntHaveErrors();
        }

        $shipments = Order::query()->orderBy('id')->get()->map(fn (Order $order) => app(CreateShipment::class)->handle($order));
        $this->assertSame(['77/501', '77/502'], $shipments->pluck('carrier_reference')->all());

        $labels = app(PrintShipmentLabels::class)->handle($shipments);
        $this->assertStringEndsWith('.zip', $labels['name']);

        $zip = new ZipArchive;
        $zip->open($labels['path']);
        $this->assertSame('%PDF-501', $zip->getFromName('stitky-slovenskaposta-EB000000001SK.pdf'));
        $this->assertSame('%PDF-502', $zip->getFromName('stitky-slovenskaposta-EB000000002SK.pdf'));
        $zip->close();
    }

    public function test_credentials_and_settings_page(): void
    {
        try {
            app(SlovenskaPostaCarrier::class)->testConnection();
            $this->fail('Chýbajúce údaje mali vyhodiť výnimku.');
        } catch (CarrierException $e) {
            $this->assertSame(CarrierException::CONFIGURATION, $e->type);
        }

        $carrier = $this->carrier();
        $this->assertTrue($carrier->isConfigured());

        // Správne údaje: server odpovie (aj chybou požiadavky), nič sa nevytvorí.
        Http::fake(['*' => Http::response('<html>Not found</html>', 404)]);
        $carrier->testConnection();
        Http::assertNotSent(fn (Request $request) => $request->method() !== 'GET');

        Http::swap(new Factory);
        Http::fake(['*' => Http::response('<html>Unauthorized</html>', 401)]);

        try {
            $carrier->testConnection();
            $this->fail('Nesprávne údaje mali vyhodiť výnimku.');
        } catch (CarrierException $e) {
            $this->assertSame(CarrierException::CONFIGURATION, $e->type);
        }

        $this->actingAs($this->superAdmin());
        $this->get('/admin/settings/slovenskaposta')->assertOk()->assertSee('Web ePH API');
        Livewire::test(ManageSlovenskaPostaSettings::class)
            ->fillForm(['parcel_category' => 'ek', 'payment_type' => 'up', 'contract' => true])
            ->call('save')->assertHasNoFormErrors();

        $settings = app(SlovenskaPostaSettings::class)->refresh();
        $this->assertSame('ek', $settings->parcel_category);
        $this->assertTrue($settings->contract);
        $this->assertSame('kluc-123', $settings->api_key);
    }
}
