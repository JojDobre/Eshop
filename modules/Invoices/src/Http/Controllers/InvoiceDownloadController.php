<?php

namespace Modules\Invoices\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Invoices\InvoicePdf;
use Modules\Invoices\Models\Invoice;
use Modules\Orders\Models\Order;

class InvoiceDownloadController extends Controller
{
    /**
     * Doklad pre zákazníka (overenie cez náhodný kľúč objednávky z e-mailu).
     */
    public function customer(string $token, string $number, InvoicePdf $pdf): Response
    {
        $order = Order::query()->where('token', $token)->firstOrFail();
        $invoice = Invoice::query()->where('order_id', $order->id)->where('number', $number)->firstOrFail();

        return $this->pdf($invoice, $pdf);
    }

    /**
     * Doklad pre administráciu.
     */
    public function admin(Invoice $invoice, InvoicePdf $pdf): Response
    {
        abort_unless((bool) (auth()->user()?->can('invoices.manage') || auth()->user()?->can('orders.manage')), 403);

        return $this->pdf($invoice, $pdf);
    }

    private function pdf(Invoice $invoice, InvoicePdf $pdf): Response
    {
        return response($pdf->render($invoice), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$invoice->fileName().'"',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
