<?php

namespace Modules\Orders\Payments;

use Modules\Orders\Models\Order;
use Modules\Payments\PaymentType;

/**
 * Zaregistrované platobné brány.
 */
class PaymentGateways
{
    /** @var list<PaymentGateway> */
    private array $gateways = [];

    public function register(PaymentGateway $gateway): void
    {
        $this->gateways[] = $gateway;
    }

    public function for(PaymentType $type): ?PaymentGateway
    {
        foreach ($this->gateways as $gateway) {
            if ($gateway->type() === $type && $gateway->isAvailable()) {
                return $gateway;
            }
        }

        return null;
    }

    /**
     * Dá sa typ platby použiť? (Platby bez brány áno, platby cez bránu iba s nastavenou bránou.)
     */
    public function supports(PaymentType $type): bool
    {
        return ! $type->requiresGateway() || $this->for($type) !== null;
    }

    /**
     * Brána, cez ktorú môže zákazník objednávku teraz zaplatiť (nezaplatená, nezrušená).
     */
    public function forPayableOrder(Order $order): ?PaymentGateway
    {
        if (! $order->payment_type->requiresGateway() || $order->isPaid() || $order->isCancelled()) {
            return null;
        }

        return $this->for($order->payment_type);
    }
}
