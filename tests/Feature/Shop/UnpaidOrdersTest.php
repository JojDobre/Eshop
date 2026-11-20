<?php

namespace Tests\Feature\Shop;

use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Modules\Catalog\Models\Product;
use Modules\Orders\Actions\HandleUnpaidOrders;
use Modules\Orders\Filament\Pages\ManageUnpaidOrderSettings;
use Modules\Orders\Mail\PaymentReminderMail;
use Modules\Orders\Models\Order;
use Modules\Orders\OrderStatus;
use Modules\Orders\UnpaidOrderSettings;

class UnpaidOrdersTest extends ShopTestCase
{
    private function placeOrder(Product $product, int $quantity = 2): Order
    {
        $this->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => $quantity]);
        $this->post(route('checkout.store'), $this->checkoutData());

        return Order::query()->latest('id')->firstOrFail();
    }

    private function settings(int $reminder, int $cancel): void
    {
        $settings = app(UnpaidOrderSettings::class);
        $settings->reminder_after_days = $reminder;
        $settings->cancel_after_days = $cancel;
        $settings->save();
    }

    public function test_reminder_is_sent_once_after_the_set_days(): void
    {
        Mail::fake();
        $this->settings(3, 7);
        $order = $this->placeOrder($this->product('Vajcia', '1.00', 50));

        $this->travel(2)->days();
        $this->assertSame(['reminded' => 0, 'cancelled' => 0], app(HandleUnpaidOrders::class)->handle());

        $this->travel(2)->days();
        $this->assertSame(['reminded' => 1, 'cancelled' => 0], app(HandleUnpaidOrders::class)->handle());
        Mail::assertSent(PaymentReminderMail::class, fn (PaymentReminderMail $mail) => $mail->order->is($order) && $mail->cancelDate !== null);
        $this->assertNotNull($order->fresh()->payment_reminded_at);

        $html = (new PaymentReminderMail($order, '9. 1. 2027'))->render();
        $this->assertStringContainsString($order->number, $html);
        $this->assertStringContainsString('9. 1. 2027', $html);

        // Druhýkrát sa už nepošle.
        $this->assertSame(['reminded' => 0, 'cancelled' => 0], app(HandleUnpaidOrders::class)->handle());
    }

    public function test_unpaid_order_is_cancelled_and_stock_returned(): void
    {
        Mail::fake();
        $this->settings(3, 7);
        $product = $this->product('Vajcia', '1.00', 50);
        $order = $this->placeOrder($product, 10);
        $this->assertSame(40, $product->fresh()->stock_quantity);

        $this->travel(8)->days();
        $result = app(HandleUnpaidOrders::class)->handle();

        $this->assertSame(1, $result['cancelled']);
        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame(50, $product->fresh()->stock_quantity);
        $this->assertTrue($order->history()->where('type', 'note')->exists());
    }

    public function test_paid_processed_and_disabled_orders_are_left_alone(): void
    {
        Mail::fake();
        $this->settings(3, 7);
        $product = $this->product('Vajcia', '1.00', 50);
        $paid = $this->placeOrder($product);
        $paid->update(['payment_status' => 'paid']);
        $processing = $this->placeOrder($product);
        $processing->update(['status' => OrderStatus::Processing]);

        $this->travel(10)->days();
        $this->assertSame(['reminded' => 0, 'cancelled' => 0], app(HandleUnpaidOrders::class)->handle());

        // Vypnuté pripomienky aj rušenie.
        $this->settings(0, 0);
        $this->placeOrder($product);
        $this->travel(30)->days();
        $this->assertSame(['reminded' => 0, 'cancelled' => 0], app(HandleUnpaidOrders::class)->handle());
        Mail::assertNotSent(PaymentReminderMail::class);
    }

    public function test_settings_page_requires_cancel_after_reminder(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(ManageUnpaidOrderSettings::class)
            ->fillForm(['reminder_after_days' => 5, 'cancel_after_days' => 3])
            ->call('save')
            ->assertHasFormErrors(['cancel_after_days']);

        Livewire::test(ManageUnpaidOrderSettings::class)
            ->fillForm(['reminder_after_days' => 3, 'cancel_after_days' => 7])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(7, app(UnpaidOrderSettings::class)->cancel_after_days);
    }
}
