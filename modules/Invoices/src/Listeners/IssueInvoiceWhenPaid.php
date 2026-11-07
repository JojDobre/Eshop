<?php

namespace Modules\Invoices\Listeners;

use App\Settings\InvoiceSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Invoices\InvoiceType;
use Modules\Invoices\IssueInvoice;
use Modules\Invoices\Mail\InvoiceMail;
use Modules\Orders\Events\OrderPaid;
use Throwable;

/**
 * Po zaplatení objednávky vystaví faktúru a pošle ju zákazníkovi.
 */
class IssueInvoiceWhenPaid
{
    public function __construct(private readonly IssueInvoice $issue, private readonly InvoiceSettings $settings) {}

    public function handle(OrderPaid $event): void
    {
        if (! $this->settings->issue_invoice_when_paid || $event->order->invoices()->where('type', InvoiceType::Invoice)->exists()) {
            return;
        }

        $invoice = $this->issue->forOrder($event->order, InvoiceType::Invoice);

        try {
            Mail::to($event->order->email, $event->order->billing_name)->locale($event->order->locale)->send(new InvoiceMail($invoice));
            $invoice->update(['sent_at' => now()]);
        } catch (Throwable $e) {
            Log::error('Invoice e-mail failed.', ['invoice' => $invoice->number, 'exception' => $e]);
        }
    }
}
