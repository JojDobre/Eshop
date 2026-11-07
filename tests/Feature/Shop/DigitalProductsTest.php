<?php

namespace Tests\Feature\Shop;

use App\Settings\CatalogSettings;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductFile;
use Modules\Orders\Actions\ChangeOrderStatus;
use Modules\Orders\Actions\SetPaymentStatus;
use Modules\Orders\Filament\Resources\Orders\Pages\ViewOrder;
use Modules\Orders\Mail\DownloadsMail;
use Modules\Orders\Models\DownloadLink;
use Modules\Orders\Models\Order;
use Modules\Orders\OrderStatus;
use Modules\Orders\PaymentStatus;
use Modules\Shipping\Models\ShippingMethod;
use Modules\Shipping\ShippingType;

class DigitalProductsTest extends ShopTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(ProductFile::DISK);
        Mail::fake();
    }

    /**
     * @param  array<string, bool|int>  $values
     */
    private function settings(array $values): void
    {
        $settings = app(CatalogSettings::class);

        foreach ($values as $key => $value) {
            $settings->{$key} = $value;
        }

        $settings->save();
    }

    private function ebook(string $price = '9.90'): Product
    {
        $product = $this->product('E-kniha', $price, attributes: ['is_digital' => true, 'track_stock' => false]);
        Storage::disk(ProductFile::DISK)->put('digital/ebook.pdf', 'PDF-OBSAH');
        $product->files()->create(['name' => 'kniha.pdf', 'path' => 'digital/ebook.pdf']);

        return $product;
    }

    private function order(Product $product, int $quantity = 1, bool $shipping = false): Order
    {
        $method = $shipping ? $this->courier() : ShippingMethod::query()->where('type', ShippingType::None)->firstOrFail();
        $this->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => $quantity]);
        $this->post(route('checkout.store'), $this->checkoutData(['shipping_method_id' => $method->id]));

        return Order::query()->latest('id')->firstOrFail();
    }

    public function test_links_are_issued_after_payment_and_file_can_be_downloaded_limited_times(): void
    {
        $this->settings(['feature_digital' => true, 'digital_download_limit' => 2, 'digital_download_days' => 7]);
        $ebook = $this->ebook();

        $this->assertFalse($ebook->requiresShipping());

        $order = $this->order($ebook);
        $this->assertSame(0, DownloadLink::query()->count());
        Mail::assertNotSent(DownloadsMail::class);

        app(SetPaymentStatus::class)->handle($order, PaymentStatus::Paid);

        $link = DownloadLink::query()->sole();
        $this->assertSame(2, $link->max_downloads);
        $this->assertTrue($link->expires_at->between(now()->addDays(6), now()->addDays(8)));
        Mail::assertSent(DownloadsMail::class, fn (DownloadsMail $mail) => $mail->hasTo('jana@example.com'));
        $this->assertStringContainsString($link->url(), DownloadsMail::linksList($order));

        $this->get($order->publicUrl())->assertSee('Súbory na stiahnutie')->assertSee('kniha.pdf');

        $this->get($link->url())->assertOk()->assertDownload('kniha.pdf');
        $this->get($link->url())->assertOk();
        $this->get($link->url())->assertStatus(410);
        $this->assertSame(2, $link->fresh()->downloads);
    }

    public function test_free_order_or_setting_delivers_immediately_and_cancel_blocks(): void
    {
        $this->settings(['feature_digital' => true, 'digital_deliver_unpaid' => true]);
        $order = $this->order($this->ebook());

        $link = DownloadLink::query()->sole();
        Mail::assertSent(DownloadsMail::class);

        app(ChangeOrderStatus::class)->handle($order, OrderStatus::Cancelled);
        $this->get($link->url())->assertStatus(410);
    }

    public function test_feature_off_means_regular_product(): void
    {
        $ebook = $this->ebook();
        $this->assertTrue($ebook->requiresShipping());

        $order = $this->order($ebook, shipping: true);
        app(SetPaymentStatus::class)->handle($order, PaymentStatus::Paid);
        $this->assertSame(0, DownloadLink::query()->count());
    }

    public function test_admin_can_resend_links_and_see_downloads(): void
    {
        $this->settings(['feature_digital' => true]);
        $ebook = $this->ebook();
        $order = $this->order($ebook, 2);
        $this->actingAs($this->superAdmin());

        Livewire::test(ViewOrder::class, ['record' => $order->id])
            ->callAction('sendDownloads')
            ->assertHasNoActionErrors();

        $this->assertSame(10, DownloadLink::query()->sole()->max_downloads);
        Mail::assertSent(DownloadsMail::class);
        $this->get('/admin/orders/'.$order->id)->assertOk()->assertSee('kniha.pdf');
        $this->get('/admin/products/'.$ebook->id.'/edit')->assertOk();
        $this->get('/admin/settings/catalog')->assertOk()->assertSee('Počet stiahnutí');
    }
}
