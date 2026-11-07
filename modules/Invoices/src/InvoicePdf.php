<?php

namespace Modules\Invoices;

use Barryvdh\DomPDF\Facade\Pdf;
use Modules\Invoices\Models\Invoice;

/**
 * PDF dokladu. Vytvára sa vždy z uloženej kópie údajov, takže je rovnaké pri každom stiahnutí.
 */
class InvoicePdf
{
    public function render(Invoice $invoice): string
    {
        $previous = app()->getLocale();
        app()->setLocale($invoice->locale);

        try {
            return Pdf::loadView('invoices::pdf.invoice', ['invoice' => $invoice, 'qr' => $this->paymentQr($invoice)])
                ->setPaper('a4')
                ->output();
        } finally {
            app()->setLocale($previous);
        }
    }

    /**
     * QR kód na platbu (doplnia ho platobné moduly cez register, napr. PAY by square).
     */
    private function paymentQr(Invoice $invoice): ?string
    {
        if ($invoice->type === InvoiceType::CreditNote || $invoice->order?->isPaid()) {
            return null;
        }

        return app(PaymentQrRegistry::class)->dataUri($invoice);
    }
}
