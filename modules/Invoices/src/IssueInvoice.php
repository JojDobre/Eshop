<?php

namespace Modules\Invoices;

use App\Settings\CompanySettings;
use App\Settings\GeneralSettings;
use App\Settings\InvoiceSettings;
use Illuminate\Support\Facades\DB;
use Modules\Invoices\Models\Invoice;
use Modules\Orders\Models\Order;
use Modules\Orders\NumberSequence;

/**
 * Vystaví doklad k objednávke. Číslo z číselného radu v tvare {predpona}{RRRR}{NNNNN}.
 */
class IssueInvoice
{
    public function __construct(
        private readonly NumberSequence $numbers,
        private readonly InvoiceSettings $settings,
    ) {}

    /**
     * Vystaví doklad daného typu, ak k objednávke ešte neexistuje (zálohová faktúra, faktúra).
     */
    public function forOrder(Order $order, InvoiceType $type): Invoice
    {
        $existing = $order->invoices()->where('type', $type)->first();

        if ($existing !== null) {
            return $existing;
        }

        return DB::transaction(function () use ($order, $type): Invoice {
            $order->loadMissing('items');
            $issuedAt = now();

            return Invoice::query()->create([
                'order_id' => $order->id,
                'type' => $type,
                'number' => $this->nextNumber($type),
                'issued_at' => $issuedAt,
                'due_at' => $type === InvoiceType::Invoice && $order->isPaid() ? $issuedAt : $issuedAt->copy()->addDays($this->settings->due_days),
                'supplied_at' => $type === InvoiceType::Proforma ? null : $issuedAt,
                'currency' => $order->currency,
                'vat_payer' => $order->vat_payer,
                'variable_symbol' => $order->variableSymbol(),
                'payment_method' => $order->payment_method_name,
                'supplier' => $this->supplier(),
                'customer' => $this->customer($order),
                'lines' => $this->lines($order),
                'vat_breakdown' => $order->vat_payer ? $order->vatBreakdown() : [],
                'total' => $order->total,
                'locale' => $order->locale,
                'note' => $this->note($order),
            ]);
        });
    }

    /**
     * Dobropis na celú sumu faktúry (napr. pri stornovaní zaplatenej objednávky alebo vrátení tovaru).
     */
    public function creditNote(Invoice $invoice): Invoice
    {
        $existing = Invoice::query()->where('related_invoice_id', $invoice->id)->where('type', InvoiceType::CreditNote)->first();

        if ($existing !== null) {
            return $existing;
        }

        return DB::transaction(fn () => Invoice::query()->create([
            'order_id' => $invoice->order_id,
            'type' => InvoiceType::CreditNote,
            'number' => $this->nextNumber(InvoiceType::CreditNote),
            'related_invoice_id' => $invoice->id,
            'issued_at' => now(),
            'due_at' => now()->addDays($this->settings->due_days),
            'supplied_at' => now(),
            'currency' => $invoice->currency,
            'vat_payer' => $invoice->vat_payer,
            'variable_symbol' => $invoice->variable_symbol,
            'payment_method' => $invoice->payment_method,
            'supplier' => $this->supplier(),
            'customer' => $invoice->customer,
            'lines' => array_map(fn (array $line) => [...$line, 'quantity' => -$line['quantity'], 'total' => -$line['total']], $invoice->lines),
            'vat_breakdown' => array_map(fn (array $row) => [...$row, 'net' => -$row['net'], 'vat' => -$row['vat'], 'gross' => -$row['gross']], $invoice->vat_breakdown),
            'total' => -$invoice->total,
            'locale' => $invoice->locale,
            'note' => $this->settings->footer,
        ]));
    }

    private function nextNumber(InvoiceType $type): string
    {
        $prefix = match ($type) {
            InvoiceType::Proforma => $this->settings->proforma_prefix,
            InvoiceType::Invoice => $this->settings->invoice_prefix,
            InvoiceType::CreditNote => $this->settings->credit_note_prefix,
        };
        $year = now()->format('Y');

        return $prefix.$year.str_pad((string) $this->numbers->next('invoice-'.$type->value.'-'.$year), 5, '0', STR_PAD_LEFT);
    }

    /**
     * @return array<string, mixed>
     */
    private function supplier(): array
    {
        $company = app(CompanySettings::class);
        $general = app(GeneralSettings::class);

        return [
            'name' => $company->company_name ?: $general->shop_name,
            'street' => $company->street,
            'city' => $company->city,
            'zip' => $company->zip,
            'country' => $company->country,
            'company_id' => $company->company_id,
            'tax_id' => $company->tax_id,
            'vat_id' => $company->vat_id,
            'iban' => $company->iban,
            'swift' => $company->swift,
            'registration' => $company->registration,
            'email' => $general->contact_email,
            'phone' => $general->contact_phone,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function customer(Order $order): array
    {
        return [
            'name' => $order->billing_name,
            'company' => $order->billing_company,
            'company_id' => $order->billing_company_id,
            'tax_id' => $order->billing_tax_id,
            'vat_id' => $order->billing_vat_id,
            'street' => $order->billing_street,
            'city' => $order->billing_city,
            'zip' => $order->billing_zip,
            'country' => $order->billing_country,
            'email' => $order->email,
            'order_number' => $order->number,
            'order_date' => $order->created_at->toDateString(),
        ];
    }

    /**
     * @return list<array{name: string, quantity: int, unit_price: int, tax_rate: float, total: int}>
     */
    private function lines(Order $order): array
    {
        $lines = $order->items->map(fn ($item) => [
            'name' => $item->fullName(),
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
            'tax_rate' => (float) $item->tax_rate,
            'total' => $item->total,
        ])->all();

        if ($order->discount > 0) {
            $lines[] = ['name' => __('invoices::invoices.discount', ['code' => $order->coupon_code], $order->locale), 'quantity' => 1,
                'unit_price' => -$order->discount, 'tax_rate' => (float) ($order->items->first()?->tax_rate ?? 0), 'total' => -$order->discount];
        }

        $lines[] = ['name' => $order->shipping_method_name, 'quantity' => 1, 'unit_price' => $order->shipping_price,
            'tax_rate' => (float) $order->shipping_tax_rate, 'total' => $order->shipping_price];

        if ($order->payment_fee > 0) {
            $lines[] = ['name' => $order->payment_method_name, 'quantity' => 1, 'unit_price' => $order->payment_fee,
                'tax_rate' => (float) $order->payment_tax_rate, 'total' => $order->payment_fee];
        }

        return $lines;
    }

    /**
     * Poznámka na doklade: pri prenesení daňovej povinnosti povinný text, potom pätička z nastavení.
     */
    private function note(Order $order): ?string
    {
        $notes = array_filter([
            $order->isReverseCharge() ? __('invoices::invoices.reverse_charge', [], $order->locale) : null,
            $this->settings->footer,
        ]);

        return $notes === [] ? null : implode("\n", $notes);
    }
}
