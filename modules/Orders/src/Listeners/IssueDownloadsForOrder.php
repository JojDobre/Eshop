<?php

namespace Modules\Orders\Listeners;

use App\Settings\CatalogSettings;
use Modules\Orders\Actions\IssueDownloads;
use Modules\Orders\Events\OrderPaid;
use Modules\Orders\Events\OrderPlaced;

/**
 * Odkazy na stiahnutie sa pošlú po zaplatení, prípadne hneď po objednávke
 * (podľa nastavenia alebo pri objednávke s nulovou sumou).
 */
class IssueDownloadsForOrder
{
    public function __construct(private readonly IssueDownloads $downloads) {}

    public function handle(OrderPlaced|OrderPaid $event): void
    {
        $order = $event->order;

        if ($event instanceof OrderPlaced && $order->total > 0 && ! rescue(fn () => app(CatalogSettings::class)->digital_deliver_unpaid, false, false)) {
            return;
        }

        if (IssueDownloads::hasDigitalItems($order)) {
            $this->downloads->handle($order);
        }
    }
}
