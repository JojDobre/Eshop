<?php

namespace Tests\Feature\Shop;

use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use LogicException;
use Modules\Invoices\Filament\RelationManagers\InvoicesRelationManager;
use Modules\Invoices\InvoicePdf;
use Modules\Invoices\InvoiceType;
use Modules\Invoices\IssueInvoice;
use Modules\Invoices\Mail\InvoiceMail;
use Modules\Invoices\Models\Invoice;
use Modules\Orders\Actions\SetPaymentStatus;
use Modules\Orders\Filament\Resources\Orders\Pages\ViewOrder;
use Modules\Orders\Mail\OrderConfirmation;
use Modules\Orders\Models\Order;
use Modules\Orders\PaymentStatus;
use Modules\Payments\PaymentType;

class InvoicesTest extends ShopTestCase
{
    private function placeOrder(PaymentType $payment = PaymentType::BankTransfer): Order
    {
        $product = $this->product('Etiópia', '12.90');
        $this->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 2]);
        $this->post(route('checkout.store'), $this->checkoutData(['payment_method_id' => $this->payment($payment)->id]));

        return Order::query()->latest('id')->firstOrFail();
    }

    public function test_bank_transfer_confirmation_has_proforma_attached(): void
    {
        Mail::fake();
        $order = $this->placeOrder();

        $proforma = $order->invoices()->firstOrFail();
        $this->assertSame(InvoiceType::Proforma, $proforma->type);
        $this->assertSame('ZF'.date('Y').'00001', $proforma->number);
        $this->assertSame($order->total, $proforma->total);
        $this->assertSame('Jana Nováková', $proforma->customer['name']);

        Mail::assertSent(OrderConfirmation::class, function (OrderConfirmation $mail) use ($proforma) {
            return collect($mail->attachments())->contains(fn ($attachment) => $attachment->as === $proforma->fileName());
        });
    }

    public function test_cash_on_delivery_order_has_no_proforma(): void
    {
        Mail::fake();
        $order = $this->placeOrder(PaymentType::CashOnDelivery);

        $this->assertSame(0, $order->invoices()->count());
    }

    public function test_invoice_is_issued_and_mailed_when_order_is_paid(): void
    {
        Mail::fake();
        $order = $this->placeOrder();

        app(SetPaymentStatus::class)->handle($order, PaymentStatus::Paid);
        app(SetPaymentStatus::class)->handle($order->fresh(), PaymentStatus::Unpaid);
        app(SetPaymentStatus::class)->handle($order->fresh(), PaymentStatus::Paid);

        $invoices = $order->invoices()->where('type', InvoiceType::Invoice)->get();
        $this->assertCount(1, $invoices);
        $this->assertSame(date('Y').'00001', $invoices->first()->number);
        $this->assertNotNull($invoices->first()->sent_at);

        Mail::assertSent(InvoiceMail::class, 1);
        Mail::assertSent(InvoiceMail::class, fn (InvoiceMail $mail) => $mail->hasTo('jana@example.com'));
    }

    public function test_invoice_is_immutable_and_credit_note_negates_it(): void
    {
        Mail::fake();
        $order = $this->placeOrder();
        $invoice = app(IssueInvoice::class)->forOrder($order, InvoiceType::Invoice);

        try {
            $invoice->update(['total' => 1]);
            $this->fail('Vystavený doklad sa nesmie meniť.');
        } catch (LogicException) {
            $this->assertSame($order->total, $invoice->fresh()->total);
        }

        $credit = app(IssueInvoice::class)->creditNote($invoice);
        $this->assertSame(InvoiceType::CreditNote, $credit->type);
        $this->assertSame(-$invoice->total, $credit->total);
        $this->assertSame($invoice->id, $credit->related_invoice_id);
        $this->assertSame($credit->id, app(IssueInvoice::class)->creditNote($invoice)->id);
    }

    public function test_pdf_renders(): void
    {
        $order = $this->placeOrder();
        $invoice = app(IssueInvoice::class)->forOrder($order, InvoiceType::Invoice);

        $this->assertStringStartsWith('%PDF', app(InvoicePdf::class)->render($invoice));
    }

    public function test_customer_and_admin_can_download_pdf(): void
    {
        $order = $this->placeOrder();
        $proforma = $order->invoices()->firstOrFail();

        $this->get(route('invoices.customer', [$order->token, $proforma->number]))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get(route('invoices.customer', [str_repeat('a', 32), $proforma->number]))->assertNotFound();
        $this->get($order->publicUrl())->assertSee($proforma->number);

        $this->get(route('invoices.admin', $proforma))->assertForbidden();
        $this->actingAs($this->superAdmin())->get(route('invoices.admin', $proforma))->assertOk();
    }

    public function test_admin_can_issue_and_send_documents(): void
    {
        Mail::fake();
        $order = $this->placeOrder(PaymentType::CashOnDelivery);
        $this->actingAs($this->superAdmin());

        Livewire::test(InvoicesRelationManager::class, ['ownerRecord' => $order, 'pageClass' => ViewOrder::class])
            ->callAction(TestAction::make('issue_invoice')->table())
            ->assertHasNoActionErrors();

        $invoice = Invoice::query()->where('order_id', $order->id)->firstOrFail();
        $this->assertSame(InvoiceType::Invoice, $invoice->type);

        Livewire::test(InvoicesRelationManager::class, ['ownerRecord' => $order, 'pageClass' => ViewOrder::class])
            ->callAction(TestAction::make('send')->table($invoice));
        Mail::assertSent(InvoiceMail::class);

        foreach (['/admin/invoices', '/admin/settings/invoices', '/admin/orders/'.$order->id] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
