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

        OrderResource::registerRelationManager(InvoicesRelationManager::class);

        // Doklady na stránke objednávky pre zákazníka.
        View::composer('theme::templates.order', function (\Illuminate\View\View $view): void {
            $order = $view->getData()['order'] ?? null;
            $view->with('documents', $order instanceof Order ? $order->invoices()->get() : collect());
        });
    }
}
