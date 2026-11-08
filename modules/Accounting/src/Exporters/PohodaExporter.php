<?php

namespace Modules\Accounting\Exporters;

use App\Core\Support\Currencies;
use App\Core\Support\Price;
use App\Settings\CompanySettings;
use Illuminate\Support\Collection;
use Modules\Accounting\AccountingExporter;
use Modules\Invoices\InvoiceType;
use Modules\Invoices\Models\Invoice;
use Modules\Orders\Models\Order;
use XMLWriter;

/**
 * Pohoda (Stormware) – XML dátová komunikácia verzie 2.0: vydané faktúry, dobropisy a prijaté objednávky.
 * Ceny dokladov sú s DPH (payVAT = true), cudzia mena sa uvádza v sekcii foreignCurrency.
 */
class PohodaExporter implements AccountingExporter
{
    private const NS = [
        'dat' => 'http://www.stormware.cz/schema/version_2/data.xsd',
        'inv' => 'http://www.stormware.cz/schema/version_2/invoice.xsd',
        'ord' => 'http://www.stormware.cz/schema/version_2/order.xsd',
        'typ' => 'http://www.stormware.cz/schema/version_2/type.xsd',
    ];

    public function key(): string
    {
        return 'pohoda';
    }

    public function label(): string
    {
        return 'Pohoda (XML)';
    }

    public function supportsOrders(): bool
    {
        return true;
    }

    public function export(Collection $invoices, Collection $orders): string
    {
        $company = app(CompanySettings::class);
        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElementNs('dat', 'dataPack', self::NS['dat']);

        foreach (['inv', 'ord', 'typ'] as $prefix) {
            $xml->writeAttribute('xmlns:'.$prefix, self::NS[$prefix]);
        }

        $xml->writeAttribute('version', '2.0');
        $xml->writeAttribute('id', 'eshop-'.now()->format('YmdHis'));
        $xml->writeAttribute('ico', (string) preg_replace('/\D/', '', (string) $company->company_id));
        $xml->writeAttribute('application', 'E-shop');
        $xml->writeAttribute('note', 'Export z e-shopu');
        $item = 0;

        foreach ($invoices as $invoice) {
            $this->item($xml, ++$item, fn () => $this->invoice($xml, $invoice));
        }

        foreach ($orders as $order) {
            $this->item($xml, ++$item, fn () => $this->order($xml, $order));
        }

        $xml->endElement();
        $xml->endDocument();

        return $xml->outputMemory();
    }

    private function item(XMLWriter $xml, int $id, \Closure $content): void
    {
        $xml->startElement('dat:dataPackItem');
        $xml->writeAttribute('version', '2.0');
        $xml->writeAttribute('id', (string) $id);
        $content();
        $xml->endElement();
    }

    private function invoice(XMLWriter $xml, Invoice $invoice): void
    {
        $foreign = $invoice->currency !== app(Currencies::class)->default();
        $xml->startElement('inv:invoice');
        $xml->writeAttribute('version', '2.0');

        $xml->startElement('inv:invoiceHeader');
        $xml->writeElement('inv:invoiceType', $invoice->type === InvoiceType::CreditNote ? 'issuedCreditNotice' : 'issuedInvoice');
        $xml->startElement('inv:number');
        $xml->writeElement('typ:numberRequested', $invoice->number);
        $xml->endElement();
        $this->optional($xml, 'inv:symVar', $invoice->variable_symbol);
        $xml->writeElement('inv:date', $invoice->issued_at->toDateString());
        $xml->writeElement('inv:dateTax', ($invoice->supplied_at ?? $invoice->issued_at)->toDateString());
        $xml->writeElement('inv:dateAccounting', $invoice->issued_at->toDateString());
        $this->optional($xml, 'inv:dateDue', $invoice->due_at?->toDateString());
        $xml->writeElement('inv:text', ($invoice->type === InvoiceType::CreditNote ? 'Dobropis ' : 'Faktúra ').$invoice->number
            .(filled($invoice->customer['order_number'] ?? null) ? ' – objednávka '.$invoice->customer['order_number'] : ''));
        $this->partner($xml, 'inv:partnerIdentity', $invoice->customer);
        $this->optional($xml, 'inv:numberOrder', $invoice->customer['order_number'] ?? null);

        if ($invoice->relatedInvoice !== null) {
            $xml->startElement('inv:originalDocument');
            $xml->writeElement('typ:sourceDocument', $invoice->relatedInvoice->number);
            $xml->endElement();
        }

        $xml->endElement();

        $xml->startElement('inv:invoiceDetail');

        foreach ($invoice->lines as $line) {
            $xml->startElement('inv:invoiceItem');
            $xml->writeElement('inv:text', mb_substr((string) $line['name'], 0, 90));
            $xml->writeElement('inv:quantity', (string) $line['quantity']);
            $xml->writeElement('inv:unit', 'ks');
            $xml->writeElement('inv:payVAT', 'true');
            $xml->writeElement('inv:rateVAT', $invoice->vat_payer ? VatRates::level((float) $line['tax_rate']) : 'none');

            if ($invoice->vat_payer && (float) $line['tax_rate'] > 0) {
                $xml->writeElement('inv:percentVAT', rtrim(rtrim(number_format((float) $line['tax_rate'], 2, '.', ''), '0'), '.'));
            }

            $xml->startElement($foreign ? 'inv:foreignCurrency' : 'inv:homeCurrency');
            $xml->writeElement('typ:unitPrice', Price::toDecimal((int) $line['unit_price'], $invoice->currency));
            $xml->endElement();
            $xml->endElement();
        }

        $xml->endElement();

        $xml->startElement('inv:invoiceSummary');
        $xml->writeElement('inv:roundingDocument', 'none');

        if ($foreign) {
            $xml->startElement('inv:foreignCurrency');
            $xml->startElement('typ:currency');
            $xml->writeElement('typ:ids', $invoice->currency);
            $xml->endElement();
            $xml->endElement();
        }

        $xml->endElement();
        $xml->endElement();
    }

    private function order(XMLWriter $xml, Order $order): void
    {
        $foreign = $order->currency !== app(Currencies::class)->default();
        $xml->startElement('ord:order');
        $xml->writeAttribute('version', '2.0');

        $xml->startElement('ord:orderHeader');
        $xml->writeElement('ord:orderType', 'receivedOrder');
        $xml->startElement('ord:number');
        $xml->writeElement('typ:numberRequested', $order->number);
        $xml->endElement();
        $xml->writeElement('ord:numberOrder', $order->number);
        $xml->writeElement('ord:date', $order->created_at->toDateString());
        $xml->writeElement('ord:text', 'Objednávka '.$order->number);
        $this->partner($xml, 'ord:partnerIdentity', [
            'name' => $order->billing_name, 'company' => $order->billing_company, 'street' => $order->billing_street, 'city' => $order->billing_city,
            'zip' => $order->billing_zip, 'country' => $order->billing_country, 'company_id' => $order->billing_company_id,
            'tax_id' => $order->billing_tax_id, 'vat_id' => $order->billing_vat_id, 'email' => $order->email, 'phone' => $order->phone,
        ]);
        $xml->endElement();

        $xml->startElement('ord:orderDetail');
        $lines = $order->items->map(fn ($item) => ['name' => $item->fullName(), 'code' => $item->sku, 'quantity' => $item->quantity, 'unit' => $item->unit_price, 'rate' => (float) $item->tax_rate])->all();
        $lines[] = ['name' => $order->shipping_method_name, 'code' => null, 'quantity' => 1, 'unit' => (int) $order->shipping_price, 'rate' => (float) $order->shipping_tax_rate];

        if ($order->payment_fee > 0) {
            $lines[] = ['name' => $order->payment_method_name, 'code' => null, 'quantity' => 1, 'unit' => (int) $order->payment_fee, 'rate' => (float) $order->payment_tax_rate];
        }

        if ($order->discount > 0) {
            $lines[] = ['name' => 'Zľava '.$order->coupon_code, 'code' => null, 'quantity' => 1, 'unit' => -$order->discount, 'rate' => (float) ($order->items->first()?->tax_rate ?? 0)];
        }

        foreach ($lines as $line) {
            $xml->startElement('ord:orderItem');
            $xml->writeElement('ord:text', mb_substr((string) $line['name'], 0, 90));
            $xml->writeElement('ord:quantity', (string) $line['quantity']);
            $xml->writeElement('ord:unit', 'ks');
            $xml->writeElement('ord:payVAT', 'true');
            $xml->writeElement('ord:rateVAT', $order->vat_payer ? VatRates::level($line['rate']) : 'none');
            $xml->startElement($foreign ? 'ord:foreignCurrency' : 'ord:homeCurrency');
            $xml->writeElement('typ:unitPrice', Price::toDecimal($line['unit'], $order->currency));
            $xml->endElement();
            $this->optional($xml, 'ord:code', $line['code']);
            $xml->endElement();
        }

        $xml->endElement();

        if ($foreign) {
            $xml->startElement('ord:orderSummary');
            $xml->startElement('ord:foreignCurrency');
            $xml->startElement('typ:currency');
            $xml->writeElement('typ:ids', $order->currency);
            $xml->endElement();
            $xml->endElement();
            $xml->endElement();
        }

        $xml->endElement();
    }

    /**
     * @param  array<string, mixed>  $party
     */
    private function partner(XMLWriter $xml, string $element, array $party): void
    {
        $xml->startElement($element);
        $xml->startElement('typ:address');
        $this->optional($xml, 'typ:company', $party['company'] ?? null);
        $this->optional($xml, 'typ:name', $party['name'] ?? null);
        $this->optional($xml, 'typ:city', $party['city'] ?? null);
        $this->optional($xml, 'typ:street', $party['street'] ?? null);
        $this->optional($xml, 'typ:zip', isset($party['zip']) ? str_replace(' ', '', (string) $party['zip']) : null);
        $this->optional($xml, 'typ:ico', $party['company_id'] ?? null);
        $this->optional($xml, 'typ:dic', $party['tax_id'] ?? null);
        // IČ DPH (Slovensko) – v českej Pohode sa DIČ uvádza v poli dic.
        $this->optional($xml, 'typ:icDph', $party['vat_id'] ?? null);

        if (filled($party['country'] ?? null)) {
            $xml->startElement('typ:country');
            $xml->writeElement('typ:ids', strtoupper((string) $party['country']));
            $xml->endElement();
        }

        $this->optional($xml, 'typ:phone', $party['phone'] ?? null);
        $this->optional($xml, 'typ:email', $party['email'] ?? null);
        $xml->endElement();
        $xml->endElement();
    }

    private function optional(XMLWriter $xml, string $name, mixed $value): void
    {
        if (filled($value)) {
            $xml->writeElement($name, (string) $value);
        }
    }
}
