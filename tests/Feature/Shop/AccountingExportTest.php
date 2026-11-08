<?php

namespace Tests\Feature\Shop;

use App\Settings\CompanySettings;
use Livewire\Livewire;
use Modules\Accounting\AccountingExporters;
use Modules\Accounting\Filament\Pages\AccountingExport;
use Modules\Invoices\InvoiceType;
use Modules\Invoices\IssueInvoice;
use Modules\Invoices\Models\Invoice;
use Modules\Orders\Models\Order;

class AccountingExportTest extends ShopTestCase
{
    private function order(): Order
    {
        app(CompanySettings::class)->fill(['company_name' => 'Obchod s.r.o.', 'company_id' => '12345678'])->save();
        $this->post(route('cart.add'), ['product_id' => $this->product('Etiópia', '12.90')->id, 'quantity' => 2]);
        $this->post(route('checkout.store'), $this->checkoutData(['billing_company' => 'Firma a.s.', 'billing_company_id' => '87654321', 'is_company' => '1']));

        return Order::query()->latest('id')->firstOrFail();
    }

    public function test_pohoda_xml_with_invoice_credit_note_and_order(): void
    {
        $order = $this->order();
        $invoice = app(IssueInvoice::class)->forOrder($order, InvoiceType::Invoice);
        $creditNote = app(IssueInvoice::class)->creditNote($invoice);

        $xml = app(AccountingExporters::class)->find('pohoda')->export(Invoice::query()->with('relatedInvoice')->whereIn('type', [InvoiceType::Invoice, InvoiceType::CreditNote])->orderBy('id')->get(), collect([$order->load('items')]));
        $doc = simplexml_load_string($xml);
        $this->assertNotFalse($doc);
        $this->assertSame('12345678', (string) $doc['ico']);

        $doc->registerXPathNamespace('inv', 'http://www.stormware.cz/schema/version_2/invoice.xsd');
        $doc->registerXPathNamespace('typ', 'http://www.stormware.cz/schema/version_2/type.xsd');
        $doc->registerXPathNamespace('ord', 'http://www.stormware.cz/schema/version_2/order.xsd');

        $this->assertSame(['issuedInvoice', 'issuedCreditNotice'], array_map('strval', $doc->xpath('//inv:invoiceType')));
        $this->assertSame($invoice->number, (string) $doc->xpath('(//inv:invoice)[1]//typ:numberRequested')[0]);
        $this->assertSame('87654321', (string) $doc->xpath('(//inv:invoice)[1]//typ:ico')[0]);
        $this->assertSame('12.90', (string) $doc->xpath('(//inv:invoice)[1]//inv:invoiceItem[1]//typ:unitPrice')[0]);
        $this->assertSame($invoice->number, (string) $doc->xpath('(//inv:invoice)[2]//typ:sourceDocument')[0]);
        $this->assertSame('receivedOrder', (string) $doc->xpath('//ord:orderType')[0]);
        $this->assertSame($creditNote->number, (string) $doc->xpath('(//inv:invoice)[2]//typ:numberRequested')[0]);
    }

    public function test_money_s3_xml_and_admin_download(): void
    {
        $order = $this->order();
        $invoice = app(IssueInvoice::class)->forOrder($order, InvoiceType::Invoice);
        app(IssueInvoice::class)->creditNote($invoice);

        $xml = simplexml_load_string(app(AccountingExporters::class)->find('money_s3')->export(Invoice::query()->with('relatedInvoice')->whereIn('type', [InvoiceType::Invoice, InvoiceType::CreditNote])->orderBy('id')->get(), collect()));
        $this->assertCount(2, $xml->SeznamFaktVyd->FaktVyd);
        $this->assertSame('0', (string) $xml->SeznamFaktVyd->FaktVyd[0]->Dobropis);
        $this->assertSame('1', (string) $xml->SeznamFaktVyd->FaktVyd[1]->Dobropis);
        $this->assertSame('Firma a.s.', (string) $xml->SeznamFaktVyd->FaktVyd[0]->DodOdb->ObchNazev);
        $this->assertSame('12.90', (string) $xml->SeznamFaktVyd->FaktVyd[0]->SeznamPolozek->Polozka[0]->Cena);

        $this->actingAs($this->superAdmin());
        $this->get('/admin/accounting-export')->assertOk();
        Livewire::test(AccountingExport::class)
            ->set('data.format', 'money_s3')->set('data.from', now()->toDateString())->set('data.to', now()->toDateString())
            ->callAction('download')
            ->assertHasNoErrors()
            ->assertFileDownloaded('money_s3-'.now()->format('Ymd').'-'.now()->format('Ymd').'.xml');

        Livewire::test(AccountingExport::class)
            ->set('data.from', now()->subYear()->toDateString())->set('data.to', now()->subYear()->toDateString())
            ->callAction('download')->assertNotified(__('accounting::admin.empty'));
    }
}
