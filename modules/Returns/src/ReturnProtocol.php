<?php

namespace Modules\Returns;

use App\Settings\CompanySettings;
use App\Settings\GeneralSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Modules\Invoices\InvoiceType;
use Modules\Invoices\IssueInvoice;
use Modules\Invoices\Models\Invoice;
use Modules\Returns\Models\ReturnRequest;

/**
 * Protokol o reklamácii alebo odstúpení (PDF) a faktúra objednávky pre dobropis.
 */
final class ReturnProtocol
{
    public static function pdf(ReturnRequest $request): string
    {
        $company = app(CompanySettings::class);
        $shop = app(GeneralSettings::class);

        return Pdf::loadView('returns::pdf.protocol', [
            'request' => $request->loadMissing('order'),
            'shop' => [
                'name' => $company->company_name ?: $shop->shop_name,
                'address' => trim($company->street.', '.$company->zip.' '.$company->city, ', '),
                'email' => $shop->contact_email,
            ],
        ])->setPaper('a4')->output();
    }

    /** Faktúra objednávky (null, ak modul dokladov nie je zapnutý alebo faktúra neexistuje). */
    public static function invoiceFor(ReturnRequest $request): ?object
    {
        if (! class_exists(Invoice::class) || ! class_exists(IssueInvoice::class)) {
            return null;
        }

        try {
            return Invoice::query()->where('order_id', $request->order_id)
                ->where('type', InvoiceType::Invoice)->latest('id')->first();
        } catch (\Throwable) {
            return null;
        }
    }
}
