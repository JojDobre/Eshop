<?php

namespace Tests\Feature\Shop;

use App\Core\Mail\TemplateMail;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Modules\Orders\Actions\ChangeOrderStatus;
use Modules\Orders\Actions\SetPaymentStatus;
use Modules\Orders\Filament\Resources\GiftVouchers\Pages\ManageGiftVouchers;
use Modules\Orders\GiftVouchers;
use Modules\Orders\GiftVoucherSettings;
use Modules\Orders\Models\GiftVoucher;
use Modules\Orders\Models\Order;
use Modules\Orders\OrderStatus;
use Modules\Orders\PaymentStatus;
use Modules\Payments\PaymentType;

class GiftVouchersTest extends ShopTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        app(GiftVoucherSettings::class)->fill(['enabled' => true, 'validity_months' => 12])->save();
    }

    private function voucher(int $amount = 5000, array $attributes = []): GiftVoucher
    {
        return GiftVoucher::query()->create(['code' => 'GVTEST1234AB', 'initial_amount' => $amount, 'balance' => $amount, 'currency' => 'EUR', ...$attributes]);
    }

    private function cart(string $price): void
    {
        $this->post(route('cart.add'), ['product_id' => $this->product('Káva', $price)->id]);
        $this->payment(PaymentType::BankTransfer)->shippingMethods()->sync([]);
    }

    public function test_voucher_pays_part_of_order_and_is_refunded_on_cancel(): void
    {
        $voucher = $this->voucher(1500);
        $this->cart('40.00');

        // Kód s medzerami a malými písmenami sa prijme.
        $this->post(route('voucher.apply'), ['code' => 'gvte-st12-34ab'])->assertSessionHas('success');
        $this->get(route('cart.show'))->assertSee('GVTE-ST12-34AB');
        $this->get(route('checkout.show'))->assertSee(__('theme::shop.amount_due'));

        $this->post(route('checkout.store'), $this->checkoutData(['shipping_method_id' => $this->pickup()->id]))->assertSessionDoesntHaveErrors();
        $order = Order::query()->sole();

        $this->assertSame(1500, $order->voucher_amount);
        $this->assertSame('GVTEST1234AB', $order->voucher_code);
        $this->assertSame($order->total - 1500, $order->amountDue());
        $this->assertSame(PaymentStatus::Unpaid, $order->payment_status);
        $this->assertSame(0, $voucher->fresh()->balance);
        // QR platba a pokyny na prevod sú na zostávajúcu sumu.
        $this->assertSame($order->amountDue(), $order->paymentQrData()?->amount ?? $order->amountDue());
        $this->get($order->publicUrl())->assertSee(__('theme::shop.gift_voucher_paid'));
        $this->assertNull(session(GiftVouchers::SESSION_KEY));

        app(ChangeOrderStatus::class)->handle($order, OrderStatus::Cancelled);
        $this->assertSame(1500, $voucher->fresh()->balance);
        // Druhé zrušenie nevráti peniaze znova.
        app(GiftVouchers::class)->refundForOrder($order->fresh());
        $this->assertSame(1500, $voucher->fresh()->balance);
    }

    public function test_voucher_covering_whole_order_marks_it_paid(): void
    {
        $voucher = $this->voucher(10000);
        $this->cart('20.00');
        $this->post(route('voucher.apply'), ['code' => $voucher->code]);
        $this->post(route('checkout.store'), $this->checkoutData(['shipping_method_id' => $this->pickup()->id]))->assertSessionDoesntHaveErrors();

        $order = Order::query()->sole();
        $this->assertSame($order->total, $order->voucher_amount);
        $this->assertSame(0, $order->amountDue());
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame(10000 - $order->total, $voucher->fresh()->balance);
    }

    public function test_invalid_expired_and_disabled_vouchers(): void
    {
        $this->cart('20.00');
        $this->post(route('voucher.apply'), ['code' => 'NEEXISTUJE'])->assertSessionHas('error', __('orders::messages.voucher_invalid'));

        $this->voucher(1000, ['expires_at' => now()->subDay()]);
        $this->post(route('voucher.apply'), ['code' => 'GVTEST1234AB'])->assertSessionHas('error', __('orders::messages.voucher_expired'));

        GiftVoucher::query()->update(['expires_at' => null, 'currency' => 'CZK']);
        $this->post(route('voucher.apply'), ['code' => 'GVTEST1234AB'])->assertSessionHas('error', __('orders::messages.voucher_currency', ['currency' => 'CZK']));

        app(GiftVoucherSettings::class)->fill(['enabled' => false])->save();
        $this->post(route('voucher.apply'), ['code' => 'GVTEST1234AB'])->assertNotFound();
        $this->get(route('cart.show'))->assertDontSee(__('theme::shop.gift_voucher'));
    }

    public function test_bought_voucher_is_issued_after_payment_and_emailed(): void
    {
        $product = $this->product('Darčeková poukážka 25 €', '25.00', 100, attributes: ['is_gift_voucher' => true]);
        $this->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 2]);
        $this->payment(PaymentType::BankTransfer)->shippingMethods()->sync([]);
        $this->post(route('checkout.store'), $this->checkoutData(['shipping_method_id' => $this->pickup()->id]))->assertSessionDoesntHaveErrors();
        $order = Order::query()->sole();

        $this->assertSame(0, GiftVoucher::query()->count());

        app(SetPaymentStatus::class)->handle($order, PaymentStatus::Paid);
        $vouchers = GiftVoucher::query()->get();

        $this->assertCount(2, $vouchers);
        $this->assertSame([2500, 2500], $vouchers->pluck('balance')->all());
        $this->assertSame($order->id, $vouchers->first()->order_id);
        $this->assertTrue($vouchers->first()->expires_at->isBetween(now()->addMonths(12)->subDay(), now()->addMonths(12)->addDay()));
        Mail::assertSent(TemplateMail::class, fn (TemplateMail $mail) => $mail->templateKey === 'orders.gift_voucher'
            && str_contains($mail->variables['voucher_codes'], $vouchers->first()->formattedCode()));

        // Opakované zaplatenie nevydá ďalšie poukážky.
        app(GiftVouchers::class)->issueForOrder($order->fresh());
        $this->assertSame(2, GiftVoucher::query()->count());
    }

    public function test_admin_issues_voucher_manually(): void
    {
        $this->actingAs($this->superAdmin());
        $this->get('/admin/gift-vouchers')->assertOk();
        $this->get('/admin/settings/gift-vouchers')->assertOk();

        Livewire::test(ManageGiftVouchers::class)
            ->callAction('issue', data: ['currency' => 'EUR', 'amount' => '30', 'recipient_email' => 'darcek@example.com', 'send' => true])
            ->assertHasNoActionErrors();

        $voucher = GiftVoucher::query()->sole();
        $this->assertSame(3000, $voucher->balance);
        $this->assertNull($voucher->order_id);
        Mail::assertSent(TemplateMail::class, fn (TemplateMail $mail) => $mail->hasTo('darcek@example.com'));

        app(GiftVoucherSettings::class)->fill(['enabled' => false])->save();
        $this->get('/admin/gift-vouchers')->assertForbidden();
    }
}
