<?php

namespace Modules\Accounting;

use Illuminate\Support\Collection;
use Modules\Invoices\Models\Invoice;
use Modules\Orders\Models\Order;

/**
 * Export dokladov do účtovného programu. Ďalšie formáty (OMEGA, iDoklad…) sa pridajú ako ďalší exportér.
 */
interface AccountingExporter
{
    public function key(): string;

    public function label(): string;

    /** Exportuje aj objednávky? */
    public function supportsOrders(): bool;

    /**
     * @param  Collection<int, Invoice>  $invoices  faktúry a dobropisy
     * @param  Collection<int, Order>  $orders
     */
    public function export(Collection $invoices, Collection $orders): string;
}
