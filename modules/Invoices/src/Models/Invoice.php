<?php

namespace Modules\Invoices\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Invoices\InvoiceType;
use Modules\Orders\Models\Order;

/**
 * Doklad (zálohová faktúra, faktúra, dobropis). Po vystavení sa nemení.
 *
 * @property int $id
 * @property int|null $order_id
 * @property InvoiceType $type
 * @property string $number
 * @property int|null $related_invoice_id
 * @property Carbon $issued_at
 * @property Carbon|null $due_at
 * @property Carbon|null $supplied_at
 * @property string $currency
 * @property bool $vat_payer
 * @property string|null $variable_symbol
 * @property string|null $payment_method
 * @property array<string, mixed> $supplier
 * @property array<string, mixed> $customer
 * @property list<array{name: string, quantity: int, unit_price: int, tax_rate: float, total: int}> $lines
 * @property array<string, array{rate: float, net: int, vat: int, gross: int}> $vat_breakdown
 * @property int $total
 * @property string $locale
 * @property string|null $note
 * @property Carbon|null $sent_at
 * @property-read Order|null $order
 * @property-read Invoice|null $relatedInvoice
 */
class Invoice extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'type' => InvoiceType::class,
            'issued_at' => 'date',
            'due_at' => 'date',
            'supplied_at' => 'date',
            'vat_payer' => 'boolean',
            'supplier' => 'array',
            'customer' => 'array',
            'lines' => 'array',
            'vat_breakdown' => 'array',
            'total' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Ochrana nemennosti: vystavený doklad sa dá iba označiť ako odoslaný.
        static::updating(function (Invoice $invoice): void {
            if (array_diff(array_keys($invoice->getDirty()), ['sent_at', 'updated_at']) !== []) {
                throw new \LogicException('Issued invoices cannot be changed. Issue a credit note instead.');
            }
        });
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function relatedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'related_invoice_id');
    }

    public function fileName(): string
    {
        return str_replace(' ', '-', $this->type->getLabel()).'-'.$this->number.'.pdf';
    }
}
