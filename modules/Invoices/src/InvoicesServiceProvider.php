<?php

namespace Modules\Invoices;

use App\Core\Mail\EmailTemplateDefinition;
use App\Core\Mail\EmailTemplates;
use App\Core\Modules\ModuleServiceProvider;
use Illuminate\Mail\Attachment;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Modules\Invoices\Filament\RelationManagers\InvoicesRelationManager;
use Modules\Invoices\Listeners\IssueInvoiceWhenPaid;
use Modules\Invoices\Listeners\IssueProformaForOrder;
use Modules\Invoices\Models\Invoice;
use Modules\Orders\Events\OrderPaid;
use Modules\Orders\Events\OrderPlaced;
use Modules\Orders\Filament\Resources\Orders\OrderResource;
use Modules\Orders\Models\Order;
use Modules\Orders\OrderMailAttachments;
use Modules\Payments\Qr\PaymentQr;
use Modules\Payments\Qr\PaymentQrData;

class InvoicesServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'invoices';
    }

    public function register(): void
    {
        $this->app->singleton(PaymentQrRegistry::class);

        Order::resolveRelationUsing('invoices', fn (Order $order) => $order->hasMany(Invoice::class)->orderBy('id'));
    }

    public function boot(): void
    {
        parent::boot();

        Event::listen(OrderPaid::class, IssueInvoiceWhenPaid::class);
        // Zálohová faktúra sa vystaví ešte pred odoslaním potvrdenia (potvrdenie si ju aj tak zabezpečí, vystavenie je idempotentné).
        Event::listen(OrderPlaced::class, IssueProformaForOrder::class);

        foreach (InvoiceType::cases() as $type) {
            $this->app->make(EmailTemplates::class)->register(new EmailTemplateDefinition(
                'invoices.'.$type->value,
                'invoices::mail.'.$type->value,
                ['customer_name', 'document_number', 'order_number', 'order_url', 'shop_name'],
                sample: ['customer_name' => 'Jana Nováková', 'document_number' => now()->format('Y').'00042', 'order_number' => now()->format('Y').'00042', 'order_url' => url('/')],
            ));
        }

        // Zálohová faktúra v prílohe potvrdenia objednávky s platbou prevodom.
        $this->app->make(OrderMailAttachments::class)->register(OrderMailAttachments::CONFIRMATION, function (Order $order): ?Attachment {
            if (! IssueProformaForOrder::applies($order)) {
                return null;
            }

            $proforma = app(IssueInvoice::class)->forOrder($order, InvoiceType::Proforma);

            return Attachment::fromData(fn () => app(InvoicePdf::class)->render($proforma), $proforma->fileName())->withMime('application/pdf');
        });

        // QR kód na platbu na nezaplatených dokladoch.
        $this->app->make(PaymentQrRegistry::class)->register(function (Invoice $invoice): ?string {
            $supplier = $invoice->supplier;

            if ($invoice->type === InvoiceType::CreditNote || $invoice->order?->isPaid() || blank($supplier['iban'] ?? null)) {
                return null;
            }

            return app(PaymentQr::class)->dataUri(new PaymentQrData(
                iban: $supplier['iban'],
                bic: $supplier['swift'] ?? null,
                amount: max(0, $invoice->total - (int) ($invoice->order?->voucher_amount ?? 0)),
                currency: $invoice->currency,
                variableSymbol: (string) $invoice->variable_symbol,
                beneficiaryName: (string) ($supplier['name'] ?? ''),
                message: $invoice->type->getLabel().' '.$invoice->number,
                dueDate: $invoice->due_at,
            ));
        });

        OrderResource::registerRelationManager(InvoicesRelationManager::class);

        // Doklady na stránke objednávky pre zákazníka.
        View::composer('theme::templates.order', function (\Illuminate\View\View $view): void {
            $order = $view->getData()['order'] ?? null;
            $view->with('documents', $order instanceof Order ? $order->invoices()->get() : collect());
        });
    }
}
