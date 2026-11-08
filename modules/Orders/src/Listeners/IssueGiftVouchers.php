<?php

namespace Modules\Orders\Listeners;

use Modules\Orders\Events\OrderPaid;
use Modules\Orders\GiftVouchers;

/**
 * Po zaplatení objednávky vydá kúpené darčekové poukážky a pošle kódy zákazníkovi.
 */
class IssueGiftVouchers
{
    public function __construct(private readonly GiftVouchers $vouchers) {}

    public function handle(OrderPaid $event): void
    {
        $this->vouchers->issueForOrder($event->order);
    }
}
