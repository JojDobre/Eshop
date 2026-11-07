<?php

namespace Modules\Orders;

use Closure;
use Illuminate\Mail\Attachment;
use Modules\Orders\Models\Order;

/**
 * Prílohy e-mailov o objednávke, ktoré dodávajú iné moduly (napr. zálohová faktúra, obchodné podmienky).
 */
class OrderMailAttachments
{
    public const CONFIRMATION = 'confirmation';

    /** @var array<string, list<Closure(Order): (Attachment|null)>> */
    private array $providers = [];

    /**
     * @param  Closure(Order): (Attachment|null)  $provider
     */
    public function register(string $mail, Closure $provider): void
    {
        $this->providers[$mail][] = $provider;
    }

    /**
     * @return list<Attachment>
     */
    public function for(string $mail, Order $order): array
    {
        return array_values(array_filter(array_map(
            fn (Closure $provider) => $provider($order),
            $this->providers[$mail] ?? [],
        )));
    }
}
