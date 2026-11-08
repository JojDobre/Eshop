<?php

namespace Tests\Feature\Shop;

use Livewire\Livewire;
use Modules\Orders\Actions\CreateShipment;
use Modules\Orders\Models\Order;
use Modules\Payments\PaymentType;
use Modules\Shipping\Carriers\CarrierRegistry;
use Modules\Shipping\Carriers\OwnPointsCarrier;
use Modules\Shipping\Filament\Resources\OwnPickupPoints\Pages\ManageOwnPickupPoints;
use Modules\Shipping\Models\PickupPoint;
use Modules\Shipping\Models\ShippingMethod;
use Modules\Shipping\ShippingType;

class CustomDeliveryTest extends ShopTestCase
{
    private function ownPoint(string $code, string $name, array $attributes = []): PickupPoint
    {
        return PickupPoint::query()->create(['carrier' => OwnPointsCarrier::KEY, 'code' => $code, 'name' => $name, 'street' => 'Hlavná 1', 'city' => 'Trnava', 'zip' => '91701', 'country' => 'SK', 'opening_hours' => 'Po–Pi 9–17', ...$attributes]);
    }

    private function cart(string $price = '10.00'): void
    {
        $this->post(route('cart.add'), ['product_id' => $this->product('Káva', $price)->id]);
        $this->payment(PaymentType::BankTransfer)->shippingMethods()->sync([]);
    }

    public function test_unlimited_custom_methods_with_countries_order_value_and_delivery_time(): void
    {
        $local = ShippingMethod::query()->create(['name' => 'Rozvoz po Trnave', 'type' => ShippingType::Delivery, 'price' => 200, 'delivery_time' => 'Doručíme do 24 hodín',
            'countries' => ['SK'], 'min_order' => 3000, 'zip_prefixes' => ['917']]);
        $czech = ShippingMethod::query()->create(['name' => 'Kuriér do Česka', 'type' => ShippingType::Delivery, 'price' => 500, 'countries' => ['CZ']]);
        $big = ShippingMethod::query()->create(['name' => 'Malé objednávky', 'type' => ShippingType::Delivery, 'price' => 100, 'max_order' => 2000]);

        // Malý košík: rozvoz (od 30 €) sa neponúkne, „Malé objednávky“ áno.
        $this->cart('10.00');
        $this->get(route('checkout.show'))->assertDontSee('Rozvoz po Trnave')->assertSee('Malé objednávky')->assertSee('Kuriér do Česka');

        // Väčší košík: rozvoz s dodacou lehotou, „Malé objednávky“ už nie.
        $this->app['session']->flush();
        $this->cart('40.00');
        $this->get(route('checkout.show'))->assertSee('Rozvoz po Trnave')->assertSee('Doručíme do 24 hodín')->assertDontSee('Malé objednávky')
            ->assertSee('shippingCountries', false);
        $this->post(route('checkout.store'), $this->checkoutData(['shipping_method_id' => $big->id, 'billing_zip' => '917 01']))->assertSessionHasErrors('shipping_method_id');

        // Krajina: kuriér do Česka iba pre CZ.
        $this->post(route('checkout.store'), $this->checkoutData(['shipping_method_id' => $czech->id]))
            ->assertSessionHasErrors(['shipping_method_id' => __('orders::messages.country_not_served')]);
        $this->post(route('checkout.store'), $this->checkoutData(['shipping_method_id' => $local->id, 'billing_zip' => '917 01']))->assertSessionDoesntHaveErrors();
        $this->assertSame($local->id, Order::query()->sole()->shipping_method_id);
    }

    public function test_own_pickup_points_in_checkout_and_shipments(): void
    {
        $this->ownPoint('trnava', 'Predajňa Trnava');
        $this->ownPoint('nitra', 'Predajňa Nitra', ['city' => 'Nitra']);
        $this->ownPoint('zatvorena', 'Zatvorená predajňa', ['is_active' => false]);
        $method = ShippingMethod::query()->create(['name' => 'Vyzdvihnutie v predajni', 'type' => ShippingType::PickupPoint, 'price' => 0,
            'carrier' => OwnPointsCarrier::KEY, 'carrier_options' => ['point_codes' => ['trnava', 'zatvorena']]]);

        $carrier = app(CarrierRegistry::class)->find(OwnPointsCarrier::KEY);
        $this->assertTrue($carrier->supports(ShippingType::PickupPoint));
        $this->assertSame(['list' => true], $carrier->pickupSelector($method));

        // Vyhľadávanie ponúkne iba vybrané a aktívne miesta.
        $points = $this->getJson('/pokladna/vydajne-miesta?method='.$method->id.'&q=predaj')->json('points');
        $this->assertSame(['trnava'], array_column($points, 'code'));

        $this->cart();
        $this->post(route('checkout.store'), $this->checkoutData(['shipping_method_id' => $method->id, 'pickup_point' => ['code' => 'nitra']]))->assertSessionHasErrors('pickup_point');
        $this->post(route('checkout.store'), $this->checkoutData(['shipping_method_id' => $method->id, 'pickup_point' => ['code' => 'trnava']]))->assertSessionDoesntHaveErrors();

        $order = Order::query()->sole();
        $this->assertSame('Predajňa Trnava', $order->pickup_point['name']);

        // Zásielka ako pri vlastnom kuriérovi (bez štítku, sledovacie číslo nepovinné).
        $shipment = app(CreateShipment::class)->handle($order);
        $this->assertSame(OwnPointsCarrier::KEY, $shipment->carrier);
        $this->assertNull($shipment->label_path);
        $this->assertSame(2, $carrier->syncPickupPoints());
    }

    public function test_admin_manages_own_pickup_points_and_method_fields(): void
    {
        $this->actingAs($this->superAdmin());
        $this->get('/admin/settings/own-pickup-points')->assertOk();

        Livewire::test(ManageOwnPickupPoints::class)
            ->callAction('create', data: ['name' => 'Predajňa Žilina', 'street' => 'Mariánske nám. 1', 'city' => 'Žilina', 'zip' => '01001', 'country' => 'SK', 'opening_hours' => 'Po–Pi 9–18', 'is_active' => true])
            ->assertHasNoActionErrors();

        $point = PickupPoint::query()->sole();
        $this->assertSame(OwnPointsCarrier::KEY, $point->carrier);
        $this->assertSame(10, strlen($point->code));

        // Kuriérske výdajné miesta sa v správe vlastných miest neukazujú.
        PickupPoint::query()->create(['carrier' => 'gls', 'code' => 'X1', 'name' => 'GLS ParcelShop', 'country' => 'SK']);
        $this->get('/admin/settings/own-pickup-points')->assertSee('Predajňa Žilina')->assertDontSee('GLS ParcelShop');

        $this->get('/admin/settings/shipping/create')->assertOk()->assertSee(__('shipping::admin.delivery_time'));
    }
}
