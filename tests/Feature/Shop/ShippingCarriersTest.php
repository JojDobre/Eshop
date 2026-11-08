<?php

namespace Tests\Feature\Shop;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Modules\Catalog\Models\ProductType;
use Modules\Orders\Actions\CreateShipment;
use Modules\Orders\Actions\PrintShipmentLabels;
use Modules\Orders\Actions\TrackShipments;
use Modules\Orders\Filament\Resources\Orders\Pages\ViewOrder;
use Modules\Orders\Mail\OrderStatusMail;
use Modules\Orders\Models\Order;
use Modules\Orders\OrderStatus;
use Modules\Orders\Shipping\CarrierHealth;
use Modules\Payments\PaymentType;
use Modules\Shipping\Carriers\CarrierRegistry;
use Modules\Shipping\Carriers\ShipmentStatus;
use Modules\Shipping\Filament\Resources\ShippingMethods\ShippingMethodResource;
use Modules\Shipping\Models\PickupPoint;
use Modules\Shipping\Models\ShippingMethod;
use Modules\Shipping\ShippingType;
use Tests\Support\FakeCarrier;

class ShippingCarriersTest extends ShopTestCase
{
    private FakeCarrier $carrier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->carrier = new FakeCarrier;
        app(CarrierRegistry::class)->register($this->carrier);
        app(CarrierRegistry::class)->register(new FakeCarrier('fakewidget', widget: true));
        Storage::fake('local');
    }

    private function method(array $attributes = []): ShippingMethod
    {
        return ShippingMethod::query()->create([
            'name' => 'Výdajné miesto Fake', 'type' => ShippingType::PickupPoint, 'price' => 299, 'carrier' => 'fake', 'position' => 0, ...$attributes,
        ]);
    }

    private function point(string $code = 'P100', string $city = 'Bratislava'): PickupPoint
    {
        return PickupPoint::query()->create(['carrier' => 'fake', 'code' => $code, 'name' => 'Trafika '.$code, 'street' => 'Hlavná 5', 'city' => $city, 'zip' => '81101', 'country' => 'SK']);
    }

    private function checkout(ShippingMethod $method, array $extra = []): TestResponse
    {
        $this->post(route('cart.add'), ['product_id' => $this->product('Káva')->id]);
        $this->payment(PaymentType::BankTransfer)->shippingMethods()->sync([]);

        return $this->post(route('checkout.store'), $this->checkoutData(['shipping_method_id' => $method->id, ...$extra]));
    }

    public function test_pickup_point_is_chosen_from_the_shop_list(): void
    {
        $method = $this->method();
        $this->point('P100');
        $this->point('P200', 'Košice');

        $this->getJson(route('checkout.pickup-points', ['method' => $method->id, 'q' => 'košice']))
            ->assertOk()->assertJsonCount(1, 'points')->assertJsonPath('points.0.code', 'P200');

        $this->post(route('cart.add'), ['product_id' => $this->product('Čaj')->id]);
        $this->get(route('checkout.show'))->assertOk()->assertSee('pickupPoint(', false);

        $this->checkout($method)->assertSessionHasErrors('pickup_point.code');
        $this->checkout($method, ['pickup_point' => ['code' => 'NEEXISTUJE']])->assertSessionHasErrors('pickup_point');

        $this->checkout($method, ['pickup_point' => ['code' => 'P100', 'name' => '<b>Podvrh</b>']])->assertRedirect();

        $order = Order::query()->sole();
        $this->assertSame('Trafika P100', $order->pickup_point['name']);
        $this->assertStringContainsString('Hlavná 5, 81101 Bratislava', $order->shipping_details);
        $this->assertNull($order->shipping_street);
    }

    public function test_widget_carrier_keeps_the_point_selected_in_its_window(): void
    {
        $method = $this->method(['carrier' => 'fakewidget']);

        $this->checkout($method, ['pickup_point' => ['code' => '4321', 'name' => 'Z-BOX Námestie', 'city' => 'Nitra', 'country' => 'sk', 'carrier_code' => 'ZB-1']]);

        $order = Order::query()->sole();
        $this->assertSame(['code' => '4321', 'name' => 'Z-BOX Námestie', 'city' => 'Nitra', 'country' => 'SK', 'carrier_code' => 'ZB-1'], $order->pickup_point);
    }

    public function test_shipping_restrictions_by_product_type_and_zip(): void
    {
        $animals = ProductType::query()->get()->first(fn ($type) => $type->getTranslation('name', 'sk') === 'Zvieratá');
        $courier = $this->courier();
        $courier->update(['excluded_product_type_ids' => [$animals->id], 'zip_prefixes' => ['85']]);

        $this->post(route('cart.add'), ['product_id' => $this->product('Mačka', attributes: ['product_type_id' => $animals->id])->id]);
        $this->get(route('checkout.show'))->assertDontSee('Kuriér na adresu');

        $this->app['session']->flush();
        $this->post(route('cart.add'), ['product_id' => $this->product('Granule')->id]);
        $this->get(route('checkout.show'))->assertSee('Kuriér na adresu');
        $this->post(route('checkout.store'), $this->checkoutData(['billing_zip' => '811 01']))->assertSessionHasErrors('shipping_method_id');
        $this->post(route('checkout.store'), $this->checkoutData(['billing_zip' => '851 01']))->assertSessionDoesntHaveErrors();
    }

    public function test_shipment_label_tracking_email_and_delivery(): void
    {
        Mail::fake();
        $method = $this->method(['cost' => 250]);
        $this->point();
        $this->checkout($method, ['pickup_point' => ['code' => 'P100']]);
        $order = Order::query()->sole();
        $this->assertSame(250, $order->shipping_cost);

        $shipment = app(CreateShipment::class)->handle($order);

        $request = $this->carrier->requests[0];
        $this->assertSame($order->number, $request->reference);
        $this->assertSame('P100', $request->pickupPoint['code']);
        $this->assertSame('TRK1001', $shipment->tracking_number);
        Storage::disk('local')->assertExists($shipment->label_path);
        $this->assertSame(OrderStatus::Shipped, $order->fresh()->status);
        Mail::assertSent(OrderStatusMail::class, fn (OrderStatusMail $mail) => str_contains($mail->render(), 'https://tracking.example.com/TRK1001'));
        $this->get($order->publicUrl())->assertSee('https://tracking.example.com/TRK1001', false);

        $labels = app(PrintShipmentLabels::class)->handle(collect([$shipment]));
        $this->assertSame('%PDF-label-1001', file_get_contents($labels['path']));

        $this->carrier->status = ShipmentStatus::Delivered;
        $this->assertSame(1, app(TrackShipments::class)->handle());
        $this->assertSame(ShipmentStatus::Delivered, $shipment->fresh()->status);
        $this->assertSame(OrderStatus::Completed, $order->fresh()->status);
    }

    public function test_repeated_outage_pauses_the_carrier(): void
    {
        Cache::flush();
        $method = $this->method();
        $this->point();
        $this->checkout($method, ['pickup_point' => ['code' => 'P100']]);
        $order = Order::query()->sole();
        $shipment = app(CreateShipment::class)->handle($order);

        $this->carrier->failing = true;

        for ($i = 0; $i < CarrierHealth::FAILURE_LIMIT; $i++) {
            $shipment->newQuery()->whereKey($shipment->id)->update(['last_checked_at' => null]);
            app(TrackShipments::class)->handle();
        }

        $this->assertFalse(app(CarrierHealth::class)->isAvailable('fake'));
        $this->assertSame(ShipmentStatus::Created, $shipment->fresh()->status);
    }

    public function test_manual_carrier_and_admin_actions(): void
    {
        Mail::fake();
        $courier = $this->courier();
        $courier->update(['carrier' => 'manual', 'carrier_options' => ['tracking_url' => 'https://posta.example/sledovanie?n={number}']]);
        $this->post(route('cart.add'), ['product_id' => $this->product('Mlynček')->id]);
        $this->post(route('checkout.store'), $this->checkoutData());
        $order = Order::query()->sole();
        $this->actingAs($this->superAdmin());

        Livewire::test(ViewOrder::class, ['record' => $order->id])
            ->callAction('createShipment', ['tracking_number' => 'RR123456789SK'])
            ->assertHasNoActionErrors();

        $shipment = $order->shipments()->sole();
        $this->assertSame('https://posta.example/sledovanie?n=RR123456789SK', $shipment->trackingUrl());
        // Doručenie kuriérom nemá údaje o odbere, sledovanie sa aj tak zobrazí.
        $this->get($order->publicUrl())->assertSee('https://posta.example/sledovanie?n=RR123456789SK', false);
        $this->get('/admin/orders/'.$order->id)->assertOk()->assertSee('RR123456789SK');
        $this->get(ShippingMethodResource::getUrl('edit', ['record' => $courier]))->assertOk();
    }
}
