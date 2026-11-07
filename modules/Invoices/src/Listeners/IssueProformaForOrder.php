<?php

namespace Modules\Invoices\Listeners;

use App\Settings\InvoiceSettings;
use Modules\Invoices\InvoiceType;
use Modules\Invoices\IssueInvoice;
use Modules\Orders\Events\OrderPlaced;
use Modules\Orders\Models\Order;
use Modules\Payments\PaymentType;

/**
 * Pri objednávke s platbou prevodom vystaví zálohovú faktúru (príloha potvrdenia objednávky).
 */
class IssueProformaForOrder
{
    public function __construct(private readonly IssueInvoice $issue, private readonly InvoiceSettings $settings) {}

    public static function applies(Order $order): bool
    {
        return app(InvoiceSettings::class)->issue_proforma && $order->payment_type === PaymentType::BankTransfer;
    }

    public function handle(OrderPlaced $event): void
    {
        if (self::applies($event->order)) {
            $this->issue->forOrder($event->order, InvoiceType::Proforma);
        }
    }
}
