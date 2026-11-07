<?php

namespace Tests\Feature\Shop;

use App\Core\Mail\EmailTemplates;
use App\Filament\Resources\EmailTemplates\Pages\EditEmailTemplate;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Modules\Invoices\InvoiceType;
use Modules\Invoices\IssueInvoice;
use Modules\Invoices\Mail\InvoiceMail;
use Modules\Orders\Filament\Resources\Orders\Pages\ViewOrder;
use Modules\Orders\Mail\OrderConfirmation;
use Modules\Orders\Mail\OrderStatusMail;
use Modules\Orders\Models\Order;
use Modules\Orders\OrderStatus;

class EmailTemplatesTest extends ShopTestCase
{
    private function placeOrder(array $overrides = []): Order
    {
        $product = $this->product('Etiópia', '12.90');
        $this->post(route('cart.add'), ['product_id' => $product->id]);
        $this->post(route('checkout.store'), $this->checkoutData($overrides));

        return Order::query()->latest('id')->firstOrFail();
    }

    public function test_default_texts_are_used_with_variables(): void
    {
        $order = $this->placeOrder();
        $mail = new OrderConfirmation($order);

        $this->assertSame('Potvrdenie objednávky č. '.$order->number, $mail->envelope()->subject);
        $html = $mail->render();
        $this->assertStringContainsString('Dobrý deň Jana Nováková', $html);
        $this->assertStringContainsString('Etiópia', $html);
        $this->assertStringContainsString($order->publicUrl(), $html);
        $this->assertDoesNotMatchRegularExpression('/\{[a-z_]+\}/', strip_tags($html));
        $this->assertStringContainsString('<table', $html);
        $this->assertStringNotContainsString('x-mail::table', $html);
    }

    public function test_customized_text_is_used_and_values_are_escaped(): void
    {
        $order = $this->placeOrder(['billing_name' => '<b>Jana</b>']);
        EmailTemplate::query()->create(['key' => 'orders.confirmation', 'subject' => 'Máme to: {order_number}', 'body' => 'Ahoj {customer_name}! {order_items}']);

        $mail = new OrderConfirmation($order);
        $this->assertSame('Máme to: '.$order->number, $mail->envelope()->subject);

        $html = $mail->render();
        $this->assertStringContainsString('Ahoj &lt;b&gt;Jana&lt;/b&gt;!', $html);
        $this->assertStringContainsString('<table', $html);
    }

    public function test_status_change_notifies_customer_unless_disabled(): void
    {
        $order = $this->placeOrder();
        Mail::fake();
        $this->actingAs($this->superAdmin());

        Livewire::test(ViewOrder::class, ['record' => $order->id])
            ->callAction('changeStatus', ['status' => OrderStatus::Shipped->value, 'notify' => true]);
        Mail::assertSent(OrderStatusMail::class, fn (OrderStatusMail $mail) => $mail->hasTo('jana@example.com')
            && str_contains($mail->envelope()->subject, 'bola odoslaná'));

        // Bez upozornenia.
        Livewire::test(ViewOrder::class, ['record' => $order->id])
            ->callAction('changeStatus', ['status' => OrderStatus::Completed->value, 'notify' => false]);
        Mail::assertSent(OrderStatusMail::class, 1);

        // Vypnutý e-mail pre stav.
        app(EmailTemplates::class)->syncRecords();
        EmailTemplate::query()->where('key', 'orders.status.processing')->update(['is_active' => false]);
        Livewire::test(ViewOrder::class, ['record' => $order->id])
            ->callAction('changeStatus', ['status' => OrderStatus::Processing->value, 'notify' => true]);
        Mail::assertSent(OrderStatusMail::class, 1);
    }

    public function test_invoice_mail_uses_template(): void
    {
        $order = $this->placeOrder();
        $invoice = app(IssueInvoice::class)->forOrder($order, InvoiceType::Invoice);

        $mail = new InvoiceMail($invoice);
        $this->assertSame('Faktúra č. '.$invoice->number, $mail->envelope()->subject);
        $this->assertStringContainsString('ďakujeme za platbu', $mail->render());
    }

    public function test_admin_can_edit_preview_and_reset_template(): void
    {
        Mail::fake();
        $this->actingAs($admin = $this->superAdmin());

        $this->get('/admin/settings/email-templates')->assertOk()->assertSee('Potvrdenie objednávky (zákazník)');
        $record = EmailTemplate::query()->where('key', 'orders.confirmation')->firstOrFail();
        $this->get('/admin/settings/email-templates/'.$record->id.'/edit')->assertOk();

        // Formulár sa predvyplní predvoleným textom; nezmenený text sa neuloží.
        Livewire::test(EditEmailTemplate::class, ['record' => $record->id])
            ->assertFormSet(['body' => __('orders::mail_templates.confirmation.body')])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertNull($record->fresh()->getTranslation('body', 'sk', false) ?: null);

        Livewire::test(EditEmailTemplate::class, ['record' => $record->id])
            ->fillForm(['subject' => 'Vďaka za objednávku {order_number}', 'body' => 'Ďakujeme, {customer_name}.'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->callAction('preview')
            ->assertHasNoActionErrors()
            ->callAction('sendTest', ['email' => 'test@example.com'])
            ->assertHasNoActionErrors();
        $this->assertSame('Ďakujeme, {customer_name}.', $record->fresh()->getTranslation('body', 'sk'));

        Livewire::test(EditEmailTemplate::class, ['record' => $record->id])->callAction('reset');
        $this->assertSame('', $record->fresh()->getTranslation('body', 'sk', false));
    }
}
