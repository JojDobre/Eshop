<?php

namespace Modules\Accounting\Exporters;

use App\Core\Support\Currencies;
use App\Core\Support\Price;
use App\Settings\CompanySettings;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Accounting\AccountingExporter;
use Modules\Invoices\InvoiceType;
use XMLWriter;

/**
 * Money S3 (Solitea) – XML import vydaných faktúr (SeznamFaktVyd). Dobropis je faktúra s príznakom Dobropis.
 * Ceny položiek sú s DPH (CenaTyp 1).
 */
class MoneyS3Exporter implements AccountingExporter
{
    public function key(): string
    {
        return 'money_s3';
    }

    public function label(): string
    {
        return 'Money S3 (XML)';
    }

    public function supportsOrders(): bool
    {
        return false;
    }

    public function export(Collection $invoices, Collection $orders): string
    {
        $company = app(CompanySettings::class);
        $home = app(Currencies::class)->default();
        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('MoneyData');
        $xml->writeAttribute('ICAgendy', (string) preg_replace('/\D/', '', (string) $company->company_id));
        $xml->writeAttribute('description', 'Export z e-shopu');
        $xml->writeAttribute('ExpZkratka', '_FV');
        $xml->writeAttribute('ExpDate', now()->toDateString());
        $xml->writeAttribute('ExpTime', now()->format('H:i:s'));
        $xml->writeAttribute('VyberZaznamu', '0');
        $xml->writeAttribute('GUID', '{'.strtoupper((string) Str::uuid()).'}');
        $xml->startElement('SeznamFaktVyd');

        foreach ($invoices as $invoice) {
            $creditNote = $invoice->type === InvoiceType::CreditNote;
            $foreign = $invoice->currency !== $home;
            $money = fn (int $amount) => Price::toDecimal($amount, $invoice->currency);

            $xml->startElement('FaktVyd');
            $xml->writeElement('Doklad', $invoice->number);
            $xml->writeElement('Popis', ($creditNote ? 'Dobropis ' : 'Faktúra ').$invoice->number);
            $xml->writeElement('Vystaveno', $invoice->issued_at->toDateString());
            $xml->writeElement('DatUcPr', $invoice->issued_at->toDateString());
            $xml->writeElement('PlnenoDPH', ($invoice->supplied_at ?? $invoice->issued_at)->toDateString());
            $this->optional($xml, 'Splatno', $invoice->due_at?->toDateString());
            $this->optional($xml, 'VarSymbol', $invoice->variable_symbol);
            $this->optional($xml, 'ObjednavkaCislo', $invoice->customer['order_number'] ?? null);
            $xml->writeElement('Druh', $creditNote ? 'D' : 'N');
            $xml->writeElement('Dobropis', $creditNote ? '1' : '0');
            $this->optional($xml, 'PuvDoklad', $invoice->relatedInvoice?->number);
            $this->optional($xml, 'PlatPodm', $invoice->payment_method);
            $xml->writeElement('CenySDPH', '1');

            $customer = $invoice->customer;
            $xml->startElement('DodOdb');
            $xml->writeElement('ObchNazev', (string) ($customer['company'] ?: $customer['name'] ?? ''));
            $xml->startElement('ObchAdresa');
            $this->optional($xml, 'Ulice', $customer['street'] ?? null);
            $this->optional($xml, 'Misto', $customer['city'] ?? null);
            $this->optional($xml, 'PSC', isset($customer['zip']) ? str_replace(' ', '', (string) $customer['zip']) : null);
            $this->optional($xml, 'KodStatu', isset($customer['country']) ? strtoupper((string) $customer['country']) : null);
            $xml->endElement();
            $this->optional($xml, 'Nazev', $customer['name'] ?? null);
            $this->optional($xml, 'ICO', $customer['company_id'] ?? null);
            $this->optional($xml, 'DIC', $customer['tax_id'] ?? null);
            $this->optional($xml, 'DICSK', $customer['vat_id'] ?? null);
            $this->optional($xml, 'EMail', $customer['email'] ?? null);
            $xml->endElement();

            if ($foreign) {
                $xml->startElement('Valuty');
                $xml->startElement('Mena');
                $xml->writeElement('Kod', $invoice->currency);
                $xml->endElement();
                $xml->writeElement('Celkem', $money((int) $invoice->total));
                $xml->endElement();
            }

            $xml->startElement('SeznamPolozek');

            foreach ($invoice->lines as $line) {
                $xml->startElement('Polozka');
                $xml->writeElement('Popis', mb_substr((string) $line['name'], 0, 100));
                $xml->writeElement('PocetMJ', (string) $line['quantity']);
                $xml->writeElement('MJ', 'ks');
                $xml->writeElement('Cena', $money((int) $line['unit_price']));
                $xml->writeElement('CenaTyp', '1');
                $xml->writeElement('SazbaDPH', $invoice->vat_payer ? rtrim(rtrim(number_format((float) $line['tax_rate'], 2, '.', ''), '0'), '.') : '0');
                $xml->endElement();
            }

            $xml->endElement();
            $xml->writeElement('Celkem', $money((int) $invoice->total));
            $xml->endElement();
        }

        $xml->endElement();
        $xml->endElement();
        $xml->endDocument();

        return $xml->outputMemory();
    }

    private function optional(XMLWriter $xml, string $name, mixed $value): void
    {
        if (filled($value)) {
            $xml->writeElement($name, (string) $value);
        }
    }
}
