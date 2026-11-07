<?php

namespace Tests\Feature\Shop;

use App\Filament\Pages\Settings\ManageMailSettings;
use App\Settings\MailSettings;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\Catalog\Filament\Resources\Products\Pages\CreateProduct;
use Modules\Catalog\Filament\Resources\Products\Pages\EditProduct;
use Modules\Catalog\Models\Product;
use Modules\Orders\Filament\Resources\Orders\Pages\ViewOrder;
use Modules\Orders\Models\Order;
use Modules\Orders\OrderStatus;
use Modules\Orders\PaymentStatus;

class OrderAdminTest extends ShopTestCase
{
    private function placeOrder(Product $product, int $quantity = 2): Order
    {
        $this->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => $quantity]);
        $this->post(route('checkout.store'), $this->checkoutData());

        return Order::query()->latest('id')->firstOrFail();
    }

    public function test_admin_pages_of_shop_modules_open(): void
    {
        $product = $this->product('Etiópia');
        $order = $this->placeOrder($product);
        $this->actingAs($this->superAdmin());

        foreach (['/admin', '/admin/orders', '/admin/orders/'.$order->id, '/admin/products', '/admin/products/create',
            '/admin/products/'.$product->id.'/edit', '/admin/categories', '/admin/settings/shipping',
            '/admin/settings/payments', '/admin/pages', '/admin/settings/mail'] as $url) {
            $this->get($url)->assertOk();
        }

        $this->get('/admin/orders')->assertSee($order->number);
    }

    public function test_cancelling_an_order_returns_stock_and_restoring_takes_it_again(): void
    {
        $product = $this->product('Etiópia', stock: 5);
        $order = $this->placeOrder($product, 2);
        $this->assertSame(3, $product->fresh()->stock_quantity);

        $this->actingAs($admin = $this->superAdmin());

        Livewire::test(ViewOrder::class, ['record' => $order->id])
            ->callAction('changeStatus', ['status' => OrderStatus::Cancelled->value])
            ->assertHasNoActionErrors();

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame(5, $product->fresh()->stock_quantity);

        Livewire::test(ViewOrder::class, ['record' => $order->id])
            ->callAction('changeStatus', ['status' => OrderStatus::Processing->value]);

        $this->assertSame(3, $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('order_history', ['order_id' => $order->id, 'type' => 'status', 'user_id' => $admin->id]);
    }

    public function test_order_can_be_marked_as_paid_and_noted(): void
    {
        $order = $this->placeOrder($this->product('Etiópia'));
        $this->actingAs($this->superAdmin());

        Livewire::test(ViewOrder::class, ['record' => $order->id])->callAction('markPaid');
        $this->assertSame(PaymentStatus::Paid, $order->fresh()->payment_status);
        $this->assertNotNull($order->fresh()->paid_at);

        Livewire::test(ViewOrder::class, ['record' => $order->id])
            ->callAction('addNote', ['note' => 'Zákazník volal, pošleme zajtra.'])
            ->assertSee('Zákazník volal, pošleme zajtra.');
    }

    public function test_product_can_be_created_with_price_in_admin(): void
    {
        $this->actingAs($this->superAdmin(['locale' => 'en']));

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Nový produkt',
                'price' => '19,90',
                'track_stock' => true,
                'stock_quantity' => 7,
                'is_visible' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::query()->with('prices')->firstOrFail();
        $this->assertSame(1990, $product->priceIn('EUR'));
        // Obsah sa ukladá v jazyku obchodu, aj keď má administrátor anglické rozhranie.
        $this->assertSame(['sk' => 'Nový produkt'], $product->getTranslations('name'));

        Livewire::test(EditProduct::class, ['record' => $product->id])
            ->assertSchemaStateSet(['name' => 'Nový produkt', 'price' => '19.90'])
            ->fillForm(['price' => '24.50'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(2450, $product->fresh('prices')->priceIn('EUR'));
    }

    public function test_mail_password_is_stored_encrypted(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(ManageMailSettings::class)
            ->fillForm(['mailer' => 'smtp', 'host' => 'smtp.example.com', 'port' => 587, 'encryption' => 'tls', 'username' => 'shop', 'password' => 'tajne-heslo'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('tajne-heslo', app(MailSettings::class)->refresh()->password);
        $raw = DB::table('settings')->where('group', 'mail')->where('name', 'password')->value('payload');
        $this->assertStringNotContainsString('tajne-heslo', (string) $raw);
    }
}
