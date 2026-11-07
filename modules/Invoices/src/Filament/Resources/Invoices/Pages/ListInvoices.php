<?php

namespace Modules\Invoices\Filament\Resources\Invoices\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Invoices\Filament\Resources\Invoices\InvoiceResource;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;
}
