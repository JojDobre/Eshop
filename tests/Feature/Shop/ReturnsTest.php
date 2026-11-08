<?php

namespace Tests\Feature\Shop;

use App\Core\Mail\TemplateMail;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Modules\Invoices\InvoiceType;
use Modules\Invoices\IssueInvoice;
use Modules\Invoices\Models\Invoice;
use Modules\Orders\Models\Order;
use Modules\Returns\Filament\Resources\ReturnRequests\Pages\ListReturnRequests;
use Modules\Returns\Filament\Resources\ReturnRequests\Pages\ViewReturnRequest;
use Modules\Returns\Models\ReturnRequest;
use Modules\Returns\ReturnDeadlines;
use Modules\Returns\ReturnProtocol;
use Modules\Returns\ReturnStatus;

class ReturnsTest extends ShopTestCase
{
    private function order(): Order
    {
        $this->post(route('cart.add'), ['product_id' => $this->product('Etiópia', '12.90')->id, 'quantity' => 2]);
        $this->post(route('checkout.store'), $this->checkoutData());

        return Order::query()->latest('id')->firstOrFail();
    }

    private function complaint(Order $order): ReturnRequest
    {
        $this->post(route('returns.store', $order->token), ['type' => 'complaint', 'quantities' => [$order->items->first()->id => 1], 'reason' => 'Nefunguje.', 'preferred_resolution' => 'refund']);

        return ReturnRequest::query()->latest('id')->firstOrFail();
    }

    public function test_guest_finds_order_by_number_and_email(): void
    {
        $order = $this->order();

        $this->get(route('returns.lookup'))->assertOk()->assertSee(__('returns::returns.adr'));
        $this->post(route('returns.find'), ['number' => $order->number, 'email' => 'iny@example.com'])->assertSessionHasErrors('number');
        $this->post(route('returns.find'), ['number' => $order->number, 'email' => 'JANA@example.com'])->assertRedirect(route('returns.create', $order->token));
        $this->get(route('returns.create', $order->token))->assertSee(__('returns::returns.adr'));
    }

    public function test_outcome_protocol_credit_note_and_export(): void
    {
        $order = $this->order();
        $invoice = app(IssueInvoice::class)->forOrder($order, InvoiceType::Invoice);
        $request = $this->complaint($order);
        $this->actingAs($this->superAdmin());

        Livewire::test(ViewReturnRequest::class, ['record' => $request->id])
            ->callAction('changeStatus', ['status' => ReturnStatus::Completed->value, 'outcome' => 'refunded', 'refund_amount' => '12,90', 'resolution' => 'Peniaze vrátené.', 'notify' => false])
            ->assertHasNoActionErrors()
            ->callAction('creditNote')
            ->assertHasNoActionErrors();

        $request->refresh();
        $this->assertSame('refunded', $request->outcome);
        $this->assertSame(1290, $request->refund_amount);
        $creditNote = Invoice::query()->where('type', InvoiceType::CreditNote)->sole();
        $this->assertSame($creditNote->id, $request->credit_note_id);
        $this->assertSame($invoice->id, $creditNote->related_invoice_id);

        $pdf = ReturnProtocol::pdf($request);
        $this->assertStringStartsWith('%PDF', $pdf);
        Livewire::test(ViewReturnRequest::class, ['record' => $request->id])->callAction('protocol')->assertFileDownloaded('protokol-'.$request->number.'.pdf');

        Livewire::test(ListReturnRequests::class)->callAction('export')->assertFileDownloaded('reklamacie-'.now()->format('Y-m-d').'.csv');
    }

    public function test_shop_is_warned_before_deadline_once(): void
    {
        $order = $this->order();
        $request = $this->complaint($order);
        Mail::fake();

        $this->assertSame(0, app(ReturnDeadlines::class)->warn());

        $this->travelTo($request->deadline_at->copy()->subDays(2));
        $this->assertSame(1, app(ReturnDeadlines::class)->warn());
        $this->assertSame(0, app(ReturnDeadlines::class)->warn());
        Mail::assertSent(TemplateMail::class, fn (TemplateMail $mail) => $mail->templateKey === 'returns.deadline_warning' && str_contains($mail->variables['requests'], $request->number));
    }
}
